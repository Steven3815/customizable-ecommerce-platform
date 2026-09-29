<?php

// Customer 取得 Checkout 付款與配送方式

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

// 取得 Customer 偏好設定
$sql = "
SELECT
    email,
    preferred_payment,
    preferred_delivery

FROM CUSTOMER

WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status AS store_status,

    ss.store_status AS business_status,
    ss.store_mode

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

// 商店暫停營業
if ($store["business_status"] !== "open") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is currently closed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式
if ($store["store_mode"] !== "shopping") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
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

$stmt->execute([
    $store_id
]);

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

// Customer 預設配送方式
$preferred_delivery = $customer["preferred_delivery"];

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

    $payment_method = $method["payment_method"];

    // 如果 Customer 有設定 preferred_payment
    // 且該付款方式目前由 Store 開放
    // 就預設 selected = true
    $selected = (
        $preferred_payment !== null &&
        $preferred_payment !== "" &&
        $preferred_payment === $payment_method
    );

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

    // 如果 Customer 有設定 preferred_delivery
    // 且該配送方式目前由 Store 開放
    // 就預設 selected = true
    $selected = (
        $preferred_delivery !== null &&
        $preferred_delivery !== "" &&
        $preferred_delivery === $delivery_method
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
    "message" => "Payment and delivery methods retrieved successfully",

    "customer" => [
        "preferred_payment" => $preferred_payment,
        "preferred_delivery" => $preferred_delivery
    ],

    "store" => [
        "store_id" => (int)$store["store_id"],
        "store_name" => $store["store_name"]
    ],

    "payment_methods" => $available_payment_methods,

    "delivery_methods" => $available_delivery_methods

], JSON_UNESCAPED_UNICODE);

?>