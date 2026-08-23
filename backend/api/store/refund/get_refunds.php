<?php

// Store 取得退款列表

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Store Session
if (
    !isset($_SESSION["store_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "store"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$_SESSION["store_id"];

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否存在
$sql = "
SELECT
    store_id
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 分頁
$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;

if ($page < 1) {
    $page = 1;
}

$limit = 20;

$offset = ($page - 1) * $limit;

// 取得排序條件
$sort = $_GET["sort"] ?? "newest";

$allowed_sort = [
    "newest",
    "oldest",
    "price_high",
    "price_low"
];

if (!in_array($sort, $allowed_sort, true)) {
    echo json_encode([
        "error" => "Invalid sort"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 排序
switch ($sort) {

    case "newest":
        $orderBy = "r.requested_at DESC";
        break;

    case "oldest":
        $orderBy = "r.requested_at ASC";
        break;

    case "price_high":
        $orderBy = "o.total_amount DESC";
        break;

    case "price_low":
        $orderBy = "o.total_amount ASC";
        break;
}

// 取得退款總筆數
$countSql = "
SELECT
    COUNT(*)
FROM REFUND r

INNER JOIN ORDERS o
    ON r.order_id = o.order_id
    AND r.store_id = o.store_id

WHERE r.store_id = ?
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute([$store_id]);
$total = (int)$countStmt->fetchColumn();

$total_pages = $total > 0
    ? (int)ceil($total / $limit)
    : 0;

// 取得退款資料
$sql = "
SELECT
    r.refund_id,
    r.order_id,
    o.order_number,
    r.store_id,
    r.refund_reason,
    r.refund_description,
    r.refund_image_url,
    r.refund_status,
    r.admin_reply,
    r.requested_at,
    r.processed_at,

    o.customer_id,
    o.total_amount,

    p.paid_at

FROM REFUND r

INNER JOIN ORDERS o
    ON r.order_id = o.order_id
    AND r.store_id = o.store_id

LEFT JOIN PAYMENT p
    ON r.order_id = p.order_id
    AND r.store_id = p.store_id

WHERE r.store_id = ?

ORDER BY $orderBy

LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(
    1,
    $store_id,
    PDO::PARAM_INT
);

$stmt->bindValue(
    2,
    $limit,
    PDO::PARAM_INT
);

$stmt->bindValue(
    3,
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();
$refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 沒有退款資料
if (!$refunds) {

    echo json_encode([
        "store_id" => $store_id,
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "total_pages" => $total_pages,
        "sort" => $sort,
        "message" => "No refunds found",
        "refunds" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理退款資料
$result = [];

foreach ($refunds as $refund) {

    $order_id = (int)$refund["order_id"];

    // 取得該 Store 的訂單商品明細
    $sql = "
    SELECT
        order_item_id,
        product_id,
        spec_id,
        product_name,
        spec_name,
        quantity,
        price

    FROM ORDER_ITEM

    WHERE order_id = ?
    AND store_id = ?

    ORDER BY order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $order_id,
        $store_id
    ]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理商品資料
    foreach ($items as &$item) {
        $item["order_item_id"] = (int)$item["order_item_id"];
        $item["product_id"] = (int)$item["product_id"];

        if ($item["spec_id"] !== null) {
            $item["spec_id"] = (int)$item["spec_id"];
        }

        $item["quantity"] = (int)$item["quantity"];
        $item["price"] = (float)$item["price"];
        $item["subtotal"] = $item["quantity"] * $item["price"];
    }

    unset($item);

    // 整理退款資料
    $refund["refund_id"] = (int)$refund["refund_id"];
    $refund["order_id"] = (int)$refund["order_id"];
    $refund["store_id"] = (int)$refund["store_id"];
    $refund["customer_id"] = (int)$refund["customer_id"];
    $refund["total_amount"] = (float)$refund["total_amount"];

    // 回傳格式
    $result[] = [
        "refund_id" => $refund["refund_id"],
        "order_id" => $refund["order_id"],
        "order_number" => $refund["order_number"],
        "store_id" => $refund["store_id"],
        "customer_id" => $refund["customer_id"],
        "payment" => [
            "paid_at" => $refund["paid_at"]
        ],
        "total_amount" => $refund["total_amount"],
        "items" => $items,
        "refund_reason" => $refund["refund_reason"],
        "refund_description" => $refund["refund_description"],
        "refund_image_url" => $refund["refund_image_url"],
        "refund_status" => $refund["refund_status"],
        "admin_reply" => $refund["admin_reply"],
        "requested_at" => $refund["requested_at"],
        "processed_at" => $refund["processed_at"]
    ];
}

// 回傳
echo json_encode([
    "message" => "Refunds retrieved successfully",
    "store_id" => $store_id,
    "page" => $page,
    "limit" => $limit,
    "total" => $total,
    "total_pages" => $total_pages,
    "sort" => $sort,
    "refunds" => $result
], JSON_UNESCAPED_UNICODE);

?>