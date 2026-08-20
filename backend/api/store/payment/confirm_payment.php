<?php

// Store 確認付款

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

// 取得 JSON 資料
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

// 檢查 Order ID
if (
    !isset($data["order_id"]) ||
    $data["order_id"] === ""
) {
    echo json_encode([
        "error" => "Order ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $data["order_id"];

// 驗證 Order ID
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

// 取得付款資料
$sql = "
SELECT
    p.payment_id,
    p.order_id,
    p.store_id,
    p.payment_method,
    p.payment_status,
    p.payment_confirm_status,

    o.customer_id,
    o.product_amount,
    o.shipping_fee,
    o.total_amount,

    c.name AS customer_name,
    c.email AS customer_email

FROM PAYMENT p

JOIN ORDERS o
    ON p.order_id = o.order_id
    AND p.store_id = o.store_id

JOIN CUSTOMER c
    ON o.customer_id = c.customer_id

WHERE p.order_id = ?
AND p.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    echo json_encode([
        "error" => "Payment not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只有 ATM 與郵局轉帳需要 Store 手動確認
if (
    $payment["payment_method"] !== "atm" &&
    $payment["payment_method"] !== "post_office"
) {
    echo json_encode([
        "error" => "Only ATM or post office transfer can be confirmed manually"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查付款是否已確認
if ($payment["payment_status"] === "paid") {
    echo json_encode([
        "error" => "Payment has already been confirmed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查付款狀態
if ($payment["payment_status"] !== "processing") {
    echo json_encode([
        "error" => "Payment is not waiting for confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查付款確認狀態
if ($payment["payment_confirm_status"] !== "waiting") {
    echo json_encode([
        "error" => "Payment is not waiting for confirmation"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $pdo->beginTransaction();

    // 確認付款
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
    AND payment_status = 'processing'
    AND payment_confirm_status = 'waiting'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $payment["payment_id"],
        $order_id,
        $store_id
    ]);

    // 確認是否更新成功
    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            "Payment confirmation failed"
        );
    }

    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => "Payment confirmation failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得付款確認時間
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
    $payment["payment_id"],
    $order_id,
    $store_id
]);

$payment_time = $stmt->fetch(PDO::FETCH_ASSOC);

// 回傳結果
echo json_encode([

    "message" =>
        "Payment confirmed successfully",

    "payment" => [

        "payment_id" =>
            (int)$payment["payment_id"],

        "order_id" =>
            (int)$payment["order_id"],

        "store_id" =>
            $store_id,

        "payment_method" =>
            $payment["payment_method"],

        "payment_status" =>
            "paid",

        "payment_confirm_status" =>
            "confirmed",

        "paid_at" =>
            $payment_time["paid_at"],

        "confirmed_at" =>
            $payment_time["confirmed_at"]
    ],

    "order" => [

        "order_id" =>
            (int)$payment["order_id"],

        "store_id" =>
            $store_id,

        "product_amount" =>
            (float)$payment["product_amount"],

        "shipping_fee" =>
            (float)$payment["shipping_fee"],

        "total_amount" =>
            (float)$payment["total_amount"]
    ],

    "customer" => [

        "customer_id" =>
            (int)$payment["customer_id"],

        "name" =>
            $payment["customer_name"],

        "email" =>
            $payment["customer_email"]
    ],

    "store" => [

        "store_id" =>
            $store_id,

        "store_name" =>
            $store["store_name"]
    ]

], JSON_UNESCAPED_UNICODE);

?>