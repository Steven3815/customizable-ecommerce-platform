<?php

// Store 取得單筆訂單詳細資料
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

// 檢查 order_id
if (!isset($_GET["order_id"])) {
    echo json_encode([
        "error" => "Order ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $_GET["order_id"];

// 取得訂單
// 確認這筆訂單有商品屬於目前 Store
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
WHERE o.order_id = ?
AND EXISTS (
    SELECT 1
    FROM ORDER_ITEM oi_store
    JOIN PRODUCT p_store
        ON oi_store.product_id = p_store.product_id
    WHERE oi_store.order_id = o.order_id
    AND p_store.store_id = ?
)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id,
    $store_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {

    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得退款狀態
$sql = "
SELECT
    refund_status
FROM REFUND
WHERE order_id = ?
ORDER BY requested_at DESC
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id
]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

// 沒有退款申請
if (!$refund) {
    $refund_status = "none";
} else {
    $refund_status = $refund["refund_status"];
}

// 取得商品明細
$sql = "
SELECT
    oi.order_item_id,
    oi.product_id,
    oi.product_name,
    oi.spec_id,
    oi.spec_name,
    oi.quantity,
    oi.price
FROM ORDER_ITEM oi
JOIN PRODUCT p
    ON oi.product_id = p.product_id
WHERE oi.order_id = ?
AND p.store_id = ?
ORDER BY oi.order_item_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id,
    $store_id
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理商品明細
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

// 整理訂單資料
$order["order_id"] =
    (int)$order["order_id"];

$order["customer_id"] =
    (int)$order["customer_id"];

$order["total_amount"] =
    (float)$order["total_amount"];

// 回傳

echo json_encode([

    "store_id" =>
        $store_id,

    "order" => [

        "order_id" =>
            $order["order_id"],

        "created_at" =>
            $order["created_at"],

        "customer" => [

            "customer_id" =>
                $order["customer_id"],

            "name" =>
                $order["customer_name"],

            "phone" =>
                $order["phone"]
        ],

        "total_amount" =>
            $order["total_amount"],

        "delivery_status" =>
            $order["delivery_status"],

        "refund_status" =>
            $refund_status,

        "items" =>
            $items
    ]

], JSON_UNESCAPED_UNICODE);

?>