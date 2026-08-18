<?php

// Customer 確認已完成 ATM / 郵局轉帳

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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!isset($data["order_id"])) {
    echo json_encode([
        "error" => "Order ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = (int)$data["order_id"];

if ($order_id <= 0) {
    echo json_encode([
        "error" => "Invalid order ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

JOIN PAYMENT p
    ON o.order_id = p.order_id
    AND o.store_id = p.store_id

WHERE o.order_id = ?
AND o.customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $customer_id
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    echo json_encode([
        "error" => "Payment not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$payment["store_id"];

if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    $payment["payment_method"] !== "atm" &&
    $payment["payment_method"] !== "post_office"
) {
    echo json_encode([
        "error" => "This payment method does not support transfer confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$payment_method = $payment["payment_method"];

// 取得 Store 狀態
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status AS store_status,

    ss.store_status AS business_status,
    ss.store_mode

FROM STORE s

JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
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

// Store 帳號是否啟用
if ($store["store_status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商店是否營業
if ($store["business_status"] !== "open") {
    echo json_encode([
        "error" => "Store is currently closed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 是否為購物模式
if ($store["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_name = $store["store_name"];

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

if ($store_payment["status"] !== "active") {
    echo json_encode([
        "error" => "Selected payment method is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 已付款
if ($payment["payment_status"] === "paid") {
    echo json_encode([
        "error" => "Order has already been paid"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 已經送出轉帳確認
if ($payment["payment_status"] === "processing") {
    echo json_encode([
        "error" => "Transfer is already waiting for store confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只有 pending 可以確認轉帳
if ($payment["payment_status"] !== "pending") {
    echo json_encode([
        "error" => "Payment is not available for transfer confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新付款狀態
$sql = "
UPDATE PAYMENT

SET
    payment_status = 'processing',
    payment_confirm_status = 'waiting',
    updated_at = NOW()

WHERE payment_id = ?
AND order_id = ?
AND store_id = ?
AND payment_status = 'pending'
AND payment_method = ?
";

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $payment["payment_id"],
        $order_id,
        $store_id,
        $payment_method
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
        "error" => "Transfer confirmation failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([

    "message" => "Transfer submitted successfully",

    "payment" => [
        "payment_id" => (int)$payment["payment_id"],
        "order_id" => $order_id,
        "store_id" => $store_id,
        "payment_method" => $payment_method,
        "payment_status" => "processing",
        "payment_confirm_status" => "waiting",
        "total_amount" => (float)$payment["total_amount"]
    ],

    "store" => [
        "store_id" => $store_id,
        "store_name" => $store_name
    ]

], JSON_UNESCAPED_UNICODE);

?>