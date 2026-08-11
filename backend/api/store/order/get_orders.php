<?php

// Store 取得訂單列表
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

// 取得搜尋、篩選、排序條件
// 搜尋：訂單編號 / 客戶姓名 / 電話
$search = $_GET["search"] ?? "";

// 配送狀態
// all pending shipping completed
$status = $_GET["status"] ?? "all";

// 退款狀態
// all none pending approved rejected
$refund_status = $_GET["refund_status"] ?? "all";

// 排序 newest oldest price_high price_low
$sort = $_GET["sort"] ?? "newest";

// 分頁
$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;

if ($page < 1) {
    $page = 1;
}

$limit = 50;

$offset = ($page - 1) * $limit;

// 建立 WHERE 確認訂單中至少有一個商品屬於目前 Store
$where = "
WHERE EXISTS (
    SELECT 1
    FROM ORDER_ITEM oi_store
    JOIN PRODUCT p_store
        ON oi_store.product_id = p_store.product_id
    WHERE oi_store.order_id = o.order_id
    AND p_store.store_id = ?
)
";

$params = [$store_id];

// 搜尋
if ($search !== "") {
    $where .= "
        AND (
            o.order_id = ?
            OR c.name LIKE ?
            OR c.phone = ?
        )";
    $params[] = $search;
    $params[] = "%" . $search . "%";
    $params[] = $search;
}

// 配送狀態篩選
if ($status === "pending") {
    $where .= "
        AND o.delivery_status = 'pending'
    ";
}
elseif ($status === "shipping") {
    $where .= "
    AND o.delivery_status = 'shipping'
    ";
}
elseif ($status === "completed") {
    $where .= "
        AND o.delivery_status = 'completed'
    ";
}
elseif ($status !== "all") {
    echo json_encode([
        "error" => "Invalid delivery status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 退款狀態篩選
if ($refund_status === "none") {
    $where .= "
        AND NOT EXISTS (
            SELECT 1
            FROM REFUND r_none
            WHERE r_none.order_id = o.order_id
        )
    ";
}
elseif ($refund_status === "pending") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r_pending
            WHERE r_pending.order_id = o.order_id
            AND r_pending.refund_status = 'pending'
        )
    ";
}
elseif ($refund_status === "approved") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r_approved
            WHERE r_approved.order_id = o.order_id
            AND r_approved.refund_status = 'approved'
        )
    ";
}
elseif ($refund_status === "rejected") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r_rejected
            WHERE r_rejected.order_id = o.order_id
            AND r_rejected.refund_status = 'rejected'
        )
    ";
}
elseif ($refund_status !== "all") {
    echo json_encode([
        "error" => "Invalid refund status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 排序
switch ($sort) {
    // 最新訂單
    case "newest":
        $orderBy = "o.created_at DESC";
        break;

    // 最舊訂單
    case "oldest":
        $orderBy = "o.created_at ASC";
        break;

    // 價格最高
    case "price_high":
        $orderBy = "o.total_amount DESC";
        break;

    // 價格最低
    case "price_low":
        $orderBy = "o.total_amount ASC";
        break;

    default:
        echo json_encode([
            "error" => "Invalid sort"
        ], JSON_UNESCAPED_UNICODE);

        exit;
}

$countSql = "
SELECT COUNT(*)
FROM ORDERS o
JOIN CUSTOMER c
    ON o.customer_id = c.customer_id
$where
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$total = (int)$countStmt->fetchColumn();

$total_pages = $total > 0
    ? (int)ceil($total / $limit)
    : 0;

// 取得訂單
$sql = "
SELECT
    o.order_id,
    o.created_at,
    o.customer_id,
    c.name AS customer_name,
    c.phone,
    o.total_amount,
    o.delivery_status
FROM ORDERS o
JOIN CUSTOMER c
    ON o.customer_id = c.customer_id
$where
ORDER BY $orderBy
LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);

$param_index = 1;

foreach ($params as $param) {
    $stmt->bindValue(
        $param_index,
        $param
    );

    $param_index++;
}

$stmt->bindValue(
    $param_index,
    $limit,
    PDO::PARAM_INT
);

$param_index++;

$stmt->bindValue(
    $param_index,
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 沒有訂單
if (!$orders) {
    echo json_encode([
        "store_id" => $store_id,
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "total_pages" => $total_pages,
        "message" => "No orders found",
        "orders" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理訂單
$result = [];

foreach ($orders as $order) {
    $order_id = (int)$order["order_id"];

    // 取得最新退款狀態
    $sql = "
    SELECT
        refund_status
    FROM REFUND
    WHERE order_id = ?
    ORDER BY requested_at DESC
    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);

    $refund = $stmt->fetch(PDO::FETCH_ASSOC);

    // 沒有退款申請
    if (!$refund) {
        $current_refund_status = "none";
    }
    else {
        $current_refund_status =
            $refund["refund_status"];
    }

    // 整理回傳資料
    $result[] = [
        "order_id" =>
            $order_id,
        "created_at" =>
            $order["created_at"],
        "customer" => [
            "customer_id" =>
                (int)$order["customer_id"],
            "name" =>
                $order["customer_name"],
            "phone" =>
                $order["phone"]
        ],
        "total_amount" =>
            (float)$order["total_amount"],
        "delivery_status" =>
            $order["delivery_status"],
        "refund_status" =>
            $current_refund_status
    ];
}

echo json_encode([
    "store_id" =>
        $store_id,
    "page" =>
        $page,
    "limit" =>
        $limit,
    "total" =>
        $total,
    "total_pages" =>
        $total_pages,
    "orders" =>
        $result
], JSON_UNESCAPED_UNICODE);

?>