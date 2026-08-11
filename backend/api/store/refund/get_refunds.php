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

// 分頁
$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;

if ($page < 1) {
    $page = 1;
}

$limit = 20;

$offset = ($page - 1) * $limit;

// 取得退款總筆數
$countSql = "
SELECT COUNT(DISTINCT r.refund_id)
FROM REFUND r
JOIN ORDERS o
ON r.order_id = o.order_id

JOIN ORDER_ITEM oi
ON o.order_id = oi.order_id

JOIN PRODUCT pr
ON oi.product_id = pr.product_id

WHERE pr.store_id = ?
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute([
    $store_id
]);

$total = (int)$countStmt->fetchColumn();

$total_pages = $total > 0
    ? (int)ceil($total / $limit)
    : 0;

// 取得退款資料
$sql = "
SELECT DISTINCT
    r.refund_id,
    r.order_id,
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
JOIN ORDERS o
ON r.order_id = o.order_id

JOIN ORDER_ITEM oi
ON o.order_id = oi.order_id

JOIN PRODUCT pr
ON oi.product_id = pr.product_id

LEFT JOIN PAYMENT p
ON r.order_id = p.order_id

WHERE pr.store_id = ?
ORDER BY r.requested_at DESC
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
        "store_id" => (int)$store_id,
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "total_pages" => $total_pages,
        "message" => "No refunds found",
        "refunds" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理退款資料
$result = [];

foreach ($refunds as $refund) {
    $order_id = $refund["order_id"];

    // 取得該 Store 的商品明細
    $sql = "
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.spec_id,
        oi.product_name,
        oi.spec_name,
        oi.quantity,
        oi.price

    FROM ORDER_ITEM oi
    JOIN PRODUCT pr
    ON oi.product_id = pr.product_id
    WHERE oi.order_id = ?
    AND pr.store_id = ?
    ORDER BY oi.order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $order_id,
        $store_id
    ]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理商品資料
    foreach ($items as &$item) {

        $item["order_item_id"] =
            (int)$item["order_item_id"];

        $item["product_id"] =
            (int)$item["product_id"];

        if ($item["spec_id"] !== null) {

            $item["spec_id"] =
                (int)$item["spec_id"];
        }

        $item["quantity"] =
            (int)$item["quantity"];

        $item["price"] =
            (float)$item["price"];

        $item["subtotal"] =
            $item["quantity"] *
            $item["price"];
    }

    unset($item);

    // 整理退款資料
    $refund["refund_id"] =
        (int)$refund["refund_id"];

    $refund["order_id"] =
        (int)$refund["order_id"];

    $refund["customer_id"] =
        (int)$refund["customer_id"];

    $refund["total_amount"] =
        (float)$refund["total_amount"];

    // 回傳格式
    $result[] = [

        "refund_id" =>
            $refund["refund_id"],

        "order_id" =>
            $refund["order_id"],

        "customer_id" =>
            $refund["customer_id"],

        "payment" => [

            "paid_at" =>
                $refund["paid_at"]
        ],

        "total_amount" =>
            $refund["total_amount"],

        "items" =>
            $items,

        "refund_reason" =>
            $refund["refund_reason"],

        "refund_description" =>
            $refund["refund_description"],

        "refund_image_url" =>
            $refund["refund_image_url"],

        "refund_status" =>
            $refund["refund_status"],

        "admin_reply" =>
            $refund["admin_reply"],

        "requested_at" =>
            $refund["requested_at"],

        "processed_at" =>
            $refund["processed_at"]
    ];
}

// 回傳
echo json_encode([
    "store_id" => (int)$store_id,
    "page" => $page,
    "limit" => $limit,
    "total" => $total,
    "total_pages" => $total_pages,
    "refunds" => $result
], JSON_UNESCAPED_UNICODE);

?>