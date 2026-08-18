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

// 檢查 order_id
if (
    !isset($_GET["order_id"]) ||
    $_GET["order_id"] === ""
) {
    echo json_encode([
        "error" => "Order ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $_GET["order_id"];

// 檢查 order_id 是否為正整數
if (
    !is_numeric($order_id) ||
    floor((float)$order_id) != (float)$order_id ||
    (int)$order_id <= 0
) {
    echo json_encode([
        "error" => "Invalid order ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = (int)$order_id;

// 檢查 Store 是否存在
$sql = "
SELECT
    store_id,
    store_name,
    status
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否啟用
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得訂單
// 直接使用 ORDERS.store_id 驗證
$sql = "
SELECT
    o.order_id,
    o.store_id,
    o.created_at,
    o.customer_id,
    c.name AS customer_name,
    c.phone AS customer_phone,
    o.receiver_name,
    o.receiver_phone,
    o.receiver_address,
    o.product_amount,
    o.shipping_fee,
    o.total_amount,
    o.delivery_method,
    o.delivery_status,
    o.estimated_ship_date,
    o.estimated_arrival_date,
    o.order_date,
    o.updated_at
FROM ORDERS o
INNER JOIN CUSTOMER c
    ON o.customer_id = c.customer_id
WHERE o.order_id = ?
AND o.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果訂單不存在，或不屬於目前 Store
if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Payment
$sql = "
SELECT
    payment_id,
    store_id,
    payment_method,
    payment_status,
    payment_confirm_status,
    payment_note,
    payment_proof_image,
    paid_at,
    confirmed_at
FROM PAYMENT
WHERE order_id = ?
AND store_id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// 取得 Refund
$sql = "
SELECT
    refund_id,
    store_id,
    refund_reason,
    refund_description,
    refund_image_url,
    refund_status,
    admin_reply,
    requested_at,
    processed_at
FROM REFUND
WHERE order_id = ?
AND store_id = ?
ORDER BY requested_at DESC
LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

// 取得商品明細
$sql = "
SELECT
    oi.order_item_id,
    oi.store_id,
    oi.product_id,
    oi.spec_id,
    oi.product_name,
    oi.spec_name,
    oi.quantity,
    oi.price
FROM ORDER_ITEM oi
WHERE oi.order_id = ?
AND oi.store_id = ?
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

    $item["store_id"] =
        (int)$item["store_id"];

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
        $item["quantity"] * $item["price"];
}

unset($item);

// 整理 Payment
if ($payment) {

    $payment["payment_id"] =
        (int)$payment["payment_id"];

    $payment["store_id"] =
        (int)$payment["store_id"];
}

// 整理 Refund
if ($refund) {

    $refund["refund_id"] =
        (int)$refund["refund_id"];

    $refund["store_id"] =
        (int)$refund["store_id"];
}

// 整理訂單資料
$order["order_id"] =
    (int)$order["order_id"];

$order["store_id"] =
    (int)$order["store_id"];

$order["customer_id"] =
    (int)$order["customer_id"];

$order["product_amount"] =
    (float)$order["product_amount"];

$order["shipping_fee"] =
    (float)$order["shipping_fee"];

$order["total_amount"] =
    (float)$order["total_amount"];

// 回傳
echo json_encode([
    "store" => [
        "store_id" =>
            (int)$store["store_id"],

        "store_name" =>
            $store["store_name"]
    ],

    "order" => [
        "order_id" =>
            $order["order_id"],

        "store_id" =>
            $order["store_id"],

        "order_date" =>
            $order["order_date"],

        "created_at" =>
            $order["created_at"],

        "updated_at" =>
            $order["updated_at"],

        "customer" => [
            "customer_id" =>
                $order["customer_id"],

            "name" =>
                $order["customer_name"],

            "phone" =>
                $order["customer_phone"]
        ],

        "receiver" => [
            "name" =>
                $order["receiver_name"],

            "phone" =>
                $order["receiver_phone"],

            "address" =>
                $order["receiver_address"]
        ],

        "amount" => [
            "product_amount" =>
                $order["product_amount"],

            "shipping_fee" =>
                $order["shipping_fee"],

            "total_amount" =>
                $order["total_amount"]
        ],

        "payment" =>
            $payment ?: null,

        "delivery" => [
            "delivery_method" =>
                $order["delivery_method"],

            "delivery_status" =>
                $order["delivery_status"],

            "estimated_ship_date" =>
                $order["estimated_ship_date"],

            "estimated_arrival_date" =>
                $order["estimated_arrival_date"]
        ],

        "refund" =>
            $refund ?: null,

        "items" =>
            $items
    ]
], JSON_UNESCAPED_UNICODE);

?>