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

// 取得 Order 與 Payment
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

    p.payment_id,
    p.payment_method,
    p.payment_status,
    p.payment_confirm_status

FROM ORDERS o

INNER JOIN PAYMENT p
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

// Order 或 Payment 不存在
if (!$payment) {
    echo json_encode([
        "error" => "Payment not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$payment["store_id"];

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得付款方式
$payment_method = $payment["payment_method"];

// 只允許 ATM / 郵局轉帳
if (
    $payment_method !== "atm" &&
    $payment_method !== "post_office"
) {
    echo json_encode([
        "error" => "This payment method does not support transfer confirmation"
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
if (
    $payment["payment_status"] === "processing" &&
    $payment["payment_confirm_status"] === "waiting"
) {
    echo json_encode([
        "error" => "Transfer is already waiting for store confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只有 pending + waiting 可以確認轉帳
if (
    $payment["payment_status"] !== "pending" ||
    $payment["payment_confirm_status"] !== "waiting"
) {
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
AND payment_confirm_status = 'waiting'
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

    // 確認只有一筆 Payment 被更新
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

// 重新取得更新後的 Payment
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

WHERE payment_id = ?
AND order_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $payment["payment_id"],
    $order_id,
    $store_id
]);

$updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// 取得 Store
$sql = "
SELECT
    store_id,
    store_name
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

// 回傳
echo json_encode([

    "message" => "Transfer submitted successfully",

    "payment" => [
        "payment_id" =>
            (int)$updated_payment["payment_id"],

        "store_id" =>
            (int)$updated_payment["store_id"],

        "payment_method" =>
            $updated_payment["payment_method"],

        "amount" =>
            (float)$updated_payment["amount"],

        "payment_status" =>
            $updated_payment["payment_status"],

        "payment_confirm_status" =>
            $updated_payment["payment_confirm_status"],

        "paid_at" =>
            $updated_payment["paid_at"],

        "confirmed_at" =>
            $updated_payment["confirmed_at"],

        "created_at" =>
            $updated_payment["created_at"],

        "updated_at" =>
            $updated_payment["updated_at"]
    ],

    "order" => [

        "order_number" =>
            $payment["order_number"],

        "customer_id" =>
            (int)$payment["customer_id"],

        "store_id" =>
            (int)$payment["store_id"],

        "product_amount" =>
            (float)$payment["product_amount"],

        "shipping_fee" =>
            (float)$payment["shipping_fee"],

        "total_amount" =>
            (float)$payment["total_amount"],

        "delivery_method" =>
            $payment["delivery_method"],

        "delivery_status" =>
            $payment["delivery_status"]
    ],

    "store" => [
        "store_id" =>
            $store_id,

        "store_name" =>
            $store["store_name"] ?? null
    ]

], JSON_UNESCAPED_UNICODE);

?>