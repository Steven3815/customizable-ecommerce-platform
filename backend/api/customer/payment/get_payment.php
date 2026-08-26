<?php

// Customer 取得付款頁面資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

$sql = "
SELECT
    email,
    preferred_payment
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// 取得 order_id 和 store_id
if (
    !isset($_GET["order_id"]) ||
    !isset($_GET["store_id"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Order ID and store ID are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = (int)$_GET["order_id"];
$store_id = (int)$_GET["store_id"];

// 檢查 Order ID
if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid order ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store ID
if ($store_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得訂單資料
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.customer_id,
    o.store_id,

    o.product_amount,
    o.shipping_fee,
    o.total_amount,

    o.receiver_name,
    o.receiver_phone,
    o.receiver_address,

    o.delivery_method,
    o.delivery_status,

    s.store_name,
    s.status AS store_status,

    ss.store_status AS business_status,
    ss.store_mode

FROM ORDERS o

JOIN STORE s
    ON o.store_id = s.store_id

JOIN STORE_SETTING ss
    ON o.store_id = ss.store_id

WHERE o.order_id = ?
AND o.customer_id = ?
AND o.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id,
    $customer_id,
    $store_id
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// 檢查訂單
if (!$order) {
    http_response_code(404);
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 從訂單取得 Store ID
$store_id = (int)$order["store_id"];
$store_name = $order["store_name"];

// 檢查 Store ID
if ($store_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店帳號停用
if ($order["store_status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店暫停營業
if ($order["business_status"] !== "open") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is currently closed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式
if ($order["store_mode"] !== "shopping") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得目前 Payment
$sql = "
SELECT
    payment_id,
    order_id,
    store_id,
    payment_method,
    amount,
    payment_status,
    payment_confirm_status,
    paid_at,
    confirmed_at,
    created_at,
    updated_at

FROM PAYMENT

WHERE order_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id,
    $store_id
]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// 整理 Payment 資料

$payment_data = null;

if ($payment) {
    $payment_data = [
        "payment_id" => (int)$payment["payment_id"],
        "store_id" => (int)$payment["store_id"],
        "payment_method" => $payment["payment_method"],
        "amount" => (float)$payment["amount"],
        "payment_status" => $payment["payment_status"],
        "payment_confirm_status" => $payment["payment_confirm_status"],
        "paid_at" => $payment["paid_at"],
        "confirmed_at" => $payment["confirmed_at"],
        "created_at" => $payment["created_at"],
        "updated_at" => $payment["updated_at"]
    ];
}

// 取得 Store 開放的付款方式

$sql = "
SELECT
    store_payment_id,
    payment_method

FROM STORE_PAYMENT_METHOD

WHERE store_id = ?
AND status = 'active'

ORDER BY store_payment_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);

$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得 Store 開放的配送方式

$sql = "
SELECT
    store_delivery_id,
    delivery_method

FROM STORE_DELIVERY_METHOD

WHERE store_id = ?
AND status = 'active'

ORDER BY store_delivery_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$delivery_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customer 預設付款方式

$preferred_payment = $customer["preferred_payment"];

// 付款方式名稱

$payment_method_names = [
    "credit_card" => "信用卡",
    "atm" => "ATM轉帳",
    "post_office" => "郵局轉帳",
    "cash_on_delivery" => "貨到付款",
    "in_store" => "店內付款"
];

// 配送方式名稱

$delivery_method_names = [
    "home_delivery" => "宅配",
    "convenience_store" => "超商取貨",
    "store_pickup" => "店內自取"
];

// 整理付款方式

$available_payment_methods = [];

foreach ($payment_methods as $method) {

    $payment_method =
        $method["payment_method"];

    // 如果尚未建立 Payment且 Customer 的 preferred_payment與目前 active 的付款方式相同就預設 selected = true
    $selected = (
        !$payment &&
        $preferred_payment !== null &&
        $preferred_payment !== "" &&
        $preferred_payment === $payment_method
    );

    // 如果已經有 Payment則以實際 Payment 的付款方式為準
    if (
        $payment &&
        $payment["payment_method"] === $payment_method
    ) {
        $selected = true;
    }

    $available_payment_methods[] = [

        "store_payment_id" => (int)$method["store_payment_id"],
        "payment_method" => $payment_method,
        "name" => $payment_method_names[$payment_method] ?? $payment_method,
        "selected" => $selected
    ];
}

// 整理配送方式
$available_delivery_methods = [];

foreach ($delivery_methods as $method) {

    $delivery_method = $method["delivery_method"];

    // 配送方式以目前訂單的ORDERS.delivery_method 為準
    $selected = (
        $order["delivery_method"] !== null &&
        $order["delivery_method"] !== "" &&
        $order["delivery_method"] === $delivery_method
    );

    $available_delivery_methods[] = [
        "store_delivery_id" => (int)$method["store_delivery_id"],
        "delivery_method" => $delivery_method,
        "name" => $delivery_method_names[$delivery_method] ?? $delivery_method,
        "selected" => $selected
    ];
}

// 回傳
echo json_encode([
    "message" => "Payment information retrieved successfully",
    "order" => [
        "order_number" => $order["order_number"],
        "customer_id" => (int)$order["customer_id"],
        "store_id" => $store_id,
        "product_amount" => (float)$order["product_amount"],
        "shipping_fee" => (float)$order["shipping_fee"],
        "total_amount" => (float)$order["total_amount"],
        "receiver_name" => $order["receiver_name"],
        "receiver_email" => $customer["email"],
        "receiver_phone" => $order["receiver_phone"],
        "receiver_address" => $order["receiver_address"],
        "delivery_method" => $order["delivery_method"],
        "delivery_status" => $order["delivery_status"]
    ],
    "payment" => $payment_data,
    "customer" => [
        "preferred_payment" => $preferred_payment
    ],
    "store" => [
        "store_id" => $store_id,
        "store_name" => $store_name
    ],
    "payment_methods" => $available_payment_methods,
    "delivery_methods" => $available_delivery_methods
], JSON_UNESCAPED_UNICODE);

?>