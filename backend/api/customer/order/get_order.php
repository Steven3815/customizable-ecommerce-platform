<?php

// Customer 取得單一訂單

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得 Store ID
if (!isset($_GET["store_id"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $_GET["store_id"];

// 檢查 Store ID
if (
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$store_id;

// 取得 Order ID
if (!isset($_GET["order_id"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Order ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $_GET["order_id"];

// 檢查 Order ID
if (
    !is_numeric($order_id) ||
    floor((float)$order_id) != (float)$order_id ||
    (int)$order_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid order ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = (int)$order_id;

// 檢查 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status AS store_status,
    ss.store_mode,
    ss.refund_enable
FROM STORE s

INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    http_response_code(404);
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店帳號停用
if ($store["store_status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$refund_enable = (bool)$store["refund_enable"];

// 取得訂單
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.customer_id,
    o.store_id,

    s.store_name,

    o.order_date,
    o.order_status,

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

    o.created_at,
    o.updated_at

FROM ORDERS o

INNER JOIN STORE s
    ON o.store_id = s.store_id

WHERE o.order_id = ?
AND o.customer_id = ?
AND o.store_id = ?

LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $customer_id,
    $store_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Payment
$sql = "
SELECT
    payment_id,
    payment_method,
    amount,
    payment_status,
    payment_confirm_status,
    payment_note,
    payment_proof_image,
    paid_at,
    confirmed_at,
    created_at,
    updated_at

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

// Refund
$sql = "
SELECT
    refund_id,
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

LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

// Order Item
$sql = "
SELECT
    oi.order_item_id,
    oi.product_id,
    oi.spec_id,
    oi.product_name,
    oi.spec_name,
    oi.quantity,
    oi.price,

    (
        SELECT pi2.image_url
        FROM PRODUCT_IMAGE pi2
        WHERE pi2.product_id = oi.product_id
        AND pi2.store_id = oi.store_id
        ORDER BY
            pi2.sort_order ASC,
            pi2.image_id ASC
        LIMIT 1
    ) AS image_url

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

// 商品資料型別轉換
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

    // 商品圖片完整網址
    if (
        $item["image_url"] !== null &&
        $item["image_url"] !== ""
    ) {
        $item["image_url"] =
            "http://localhost/ecommerce-platform/backend" .
            $item["image_url"];
    }
}

unset($item);

// Payment 資料型別轉換
if ($payment) {

    $payment["payment_id"] =
        (int)$payment["payment_id"];

    $payment["amount"] =
        (float)$payment["amount"];
}

// Refund 資料型別轉換
if ($refund) {

    $refund["refund_id"] =
        (int)$refund["refund_id"];
}

// 建立訂單結果
$result = [
    "order_id" => $order_id,
    "order_number" => $order["order_number"],
    "customer_id" => (int)$order["customer_id"],
    "store_id" => (int)$order["store_id"],
    "store_name" => $order["store_name"],

    "order_date" => $order["order_date"],
    "order_status" => $order["order_status"],

    "receiver_name" => $order["receiver_name"],
    "receiver_phone" => $order["receiver_phone"],
    "receiver_address" => $order["receiver_address"],

    "product_amount" => (float)$order["product_amount"],
    "shipping_fee" => (float)$order["shipping_fee"],
    "total_amount" => (float)$order["total_amount"],

    "payment" => $payment ?: null,

    "delivery_method" => $order["delivery_method"],
    "delivery_status" => $order["delivery_status"],
    "estimated_ship_date" => $order["estimated_ship_date"],
    "estimated_arrival_date" => $order["estimated_arrival_date"],

    "refund" => $refund ?: null,

    "items" => $items,

    "created_at" => $order["created_at"],
    "updated_at" => $order["updated_at"]
];

// 回傳
echo json_encode([
    "message" => "Order retrieved successfully",
    "customer_id" => $customer_id,
    "store_id" => $store_id,
    "refund_enable" => $refund_enable,
    "order" => $result
], JSON_UNESCAPED_UNICODE);

?>