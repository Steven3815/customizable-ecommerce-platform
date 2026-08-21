<?php

// Customer ATM / 郵局轉帳付款

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Customer Session
if (
    !isset($_SESSION["customer_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = (int)$_SESSION["customer_id"];

// 檢查 Customer ID
if ($customer_id <= 0) {
    echo json_encode([
        "error" => "Invalid customer ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Customer 是否存在
$sql = "
SELECT
    customer_id
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON
if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (!isset($data["order_id"])) {
    echo json_encode([
        "error" => "Order ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $data["order_id"];

// 檢查 Order ID
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

// 取得訂單
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.customer_id,
    o.store_id,
    o.product_amount,
    o.shipping_fee,
    o.total_amount,
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
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $customer_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// 訂單不存在
if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$order["store_id"];

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 是否啟用
if ($order["store_status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店暫停營業
if ($order["business_status"] !== "open") {
    echo json_encode([
        "error" => "Store is currently closed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式
if ($order["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_name = $order["store_name"];

// 取得 Payment
$sql = "
SELECT
    payment_id,
    order_id,
    store_id,
    payment_method,
    amount,
    payment_status,
    payment_confirm_status

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

// Payment 不存在
if (!$payment) {
    echo json_encode([
        "error" => "Payment not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 從 Payment 取得付款方式
$payment_method = $payment["payment_method"];

// 只允許 ATM / 郵局
if (
    $payment_method !== "atm" &&
    $payment_method !== "post_office"
) {
    echo json_encode([
        "error" => "This payment method does not support transfer"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 已付款
if (
    $payment["payment_status"] === "paid" &&
    $payment["payment_confirm_status"] === "confirmed"
) {
    echo json_encode([
        "error" => "Order has already been paid"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 已經送出轉帳確認
if (
    $payment["payment_status"] === "processing" &&
    $payment["payment_confirm_status"] === "waiting"
) {
    echo json_encode([
        "error" => "Transfer is already waiting for store confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只有 pending + waiting 可以取得轉帳資訊
if (
    $payment["payment_status"] !== "pending" ||
    $payment["payment_confirm_status"] !== "waiting"
) {
    echo json_encode([
        "error" => "Payment is not available for transfer"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否開放此付款方式
$sql = "
SELECT
    store_payment_id,
    payment_method,
    status

FROM STORE_PAYMENT_METHOD

WHERE store_id = ?
AND payment_method = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id,
    $payment_method
]);

$store_payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store_payment) {
    echo json_encode([
        "error" => "Payment method is not available for this store"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 付款方式已停用
if ($store_payment["status"] !== "active") {
    echo json_encode([
        "error" => "Selected payment method is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Store 付款帳戶
$sql = "
SELECT
    account_id,
    bank_name,
    bank_number,
    post_office_number

FROM STORE_PAYMENT_ACCOUNT

WHERE store_id = ?
AND store_payment_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id,
    $store_payment["store_payment_id"]
]);

$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Store 付款帳戶不存在
if (!$account) {
    echo json_encode([
        "error" => "Store payment account not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理轉帳帳戶
$payment_account = [];

if ($payment_method === "atm") {

    if (
        empty($account["bank_name"]) ||
        empty($account["bank_number"])
    ) {
        echo json_encode([
            "error" => "Store bank account is not configured"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $payment_account = [
        "bank_name" =>
            $account["bank_name"],

        "bank_number" =>
            $account["bank_number"]
    ];

} elseif ($payment_method === "post_office") {

    if (empty($account["post_office_number"])) {
        echo json_encode([
            "error" => "Store post office account is not configured"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $payment_account = [
        "post_office_number" =>
            $account["post_office_number"]
    ];
}

// 回傳轉帳資訊
echo json_encode([

    "message" =>
        "Transfer information",

    "payment" => [

        "payment_id" =>
            (int)$payment["payment_id"],

        "order_number" =>
            $order["order_number"],

        "store_id" =>
            (int)$payment["store_id"],

        "payment_method" =>
            $payment_method,

        "amount" =>
            (float)$payment["amount"],

        "payment_status" =>
            $payment["payment_status"],

        "payment_confirm_status" =>
            $payment["payment_confirm_status"]
    ],

    "order" => [

        "order_number" =>
            $order["order_number"],

        "customer_id" =>
            (int)$order["customer_id"],

        "store_id" =>
            (int)$order["store_id"],

        "product_amount" =>
            (float)$order["product_amount"],

        "shipping_fee" =>
            (float)$order["shipping_fee"],

        "total_amount" =>
            (float)$order["total_amount"],

        "delivery_method" =>
            $order["delivery_method"],

        "delivery_status" =>
            $order["delivery_status"]
    ],

    "store" => [

        "store_id" =>
            $store_id,

        "store_name" =>
            $store_name
    ],

    "payment_account" =>
        $payment_account

], JSON_UNESCAPED_UNICODE);

?>