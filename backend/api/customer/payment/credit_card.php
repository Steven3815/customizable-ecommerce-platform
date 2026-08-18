<?php

// Customer 信用卡付款

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
$stmt->execute([$customer_id]);

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
if (
    !isset($data["order_id"]) ||
    !isset($data["store_id"]) ||
    !isset($data["transaction_amount"]) ||
    !isset($data["card_number"]) ||
    !isset($data["expiry_date"]) ||
    !isset($data["cvv"]) ||
    !isset($data["phone"])
) {
    echo json_encode([
        "error" => "Order ID, store ID, transaction amount, card information and phone are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = (int)$data["order_id"];
$store_id = (int)$data["store_id"];
$transaction_amount = (float)$data["transaction_amount"];

$card_number = trim($data["card_number"]);
$expiry_date = trim($data["expiry_date"]);
$cvv = trim($data["cvv"]);
$phone = trim($data["phone"]);

// 檢查 Order ID
if ($order_id <= 0) {
    echo json_encode([
        "error" => "Invalid order ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查交易金額
if ($transaction_amount <= 0) {
    echo json_encode([
        "error" => "Invalid transaction amount"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查卡號
$card_number = preg_replace(
    '/\s+/',
    '',
    $card_number
);

if (!preg_match(
    '/^\d{13,19}$/',
    $card_number
)) {
    echo json_encode([
        "error" => "Invalid card number"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查有效期限
if ($expiry_date === "") {
    echo json_encode([
        "error" => "Card expiry date is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 CVV
if (!preg_match(
    '/^\d{3}$/',
    $cvv
)) {
    echo json_encode([
        "error" => "Invalid CVV"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查手機
if ($phone === "") {
    echo json_encode([
        "error" => "Phone number is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得訂單與付款
$sql = "
SELECT
    o.order_id,
    o.customer_id,
    o.store_id,
    o.total_amount,

    p.payment_id,
    p.payment_method,
    p.payment_status,
    p.payment_confirm_status

FROM ORDERS o

LEFT JOIN PAYMENT p
    ON o.order_id = p.order_id
    AND o.store_id = p.store_id

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
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 狀態
$sql = "
SELECT
    s.store_id,
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

// Store 不存在
if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店帳號停用
if ($store["store_status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店暫停營業
if ($store["business_status"] !== "open") {
    echo json_encode([
        "error" => "Store is currently closed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式
if ($store["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Payment
if (!$order["payment_id"]) {
    echo json_encode([
        "error" => "Payment has not been created"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查付款方式
if ($order["payment_method"] !== "credit_card") {
    echo json_encode([
        "error" => "Payment method is not credit card"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查付款狀態
if ($order["payment_status"] === "paid") {
    echo json_encode([
        "error" => "Order has already been paid"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($order["payment_status"] !== "pending") {
    echo json_encode([
        "error" => "Payment is not available for credit card payment"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證付款金額
$order_total = (float)$order["total_amount"];

if (
    abs($transaction_amount - $order_total) > 0.01
) {
    echo json_encode([
        "error" => "Transaction amount does not match order total"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 模擬信用卡付款
$payment_success = true;

if (!$payment_success) {

    $sql = "
    UPDATE PAYMENT
    SET
        payment_status = 'failed',
        payment_confirm_status = 'rejected',
        updated_at = NOW()

    WHERE payment_id = ?
    AND order_id = ?
    AND store_id = ?
    AND payment_status = 'pending'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order["payment_id"],
        $order_id,
        $store_id
    ]);

    echo json_encode([
        "error" => "Payment failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新付款狀態
try {

    $pdo->beginTransaction();

    $sql = "
    UPDATE PAYMENT
    SET
        payment_status = 'paid',
        payment_confirm_status = 'confirmed',
        paid_at = NOW(),
        confirmed_at = NOW(),
        updated_at = NOW()

    WHERE payment_id = ?
    AND order_id = ?
    AND store_id = ?
    AND payment_status = 'pending'
    AND payment_method = 'credit_card'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order["payment_id"],
        $order_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {

        $pdo->rollBack();

        echo json_encode([
            "error" => "Payment status could not be updated"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $pdo->commit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => "Payment update failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得實際付款時間
$sql = "
SELECT
    paid_at,
    confirmed_at

FROM PAYMENT

WHERE payment_id = ?
AND order_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order["payment_id"],
    $order_id,
    $store_id
]);

$paid_info = $stmt->fetch(PDO::FETCH_ASSOC);

// 回傳
echo json_encode([

    "message" => "Payment successful",

    "payment" => [
        "payment_id" => (int)$order["payment_id"],
        "order_id" => $order_id,
        "store_id" => $store_id,
        "payment_method" => "credit_card",
        "transaction_amount" => $transaction_amount,
        "payment_status" => "paid",
        "payment_confirm_status" => "confirmed",
        "paid_at" => $paid_info["paid_at"],
        "confirmed_at" => $paid_info["confirmed_at"]
    ],

    "order" => [
        "order_id" => $order_id,
        "store_id" => $store_id,
        "total_amount" => $order_total
    ]

], JSON_UNESCAPED_UNICODE);

?>