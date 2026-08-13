<?php

// 更新訂單
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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON data"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查必要欄位
if (!isset($data["order_id"])) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$order_id = $data["order_id"];

// 檢查 order_id
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

// 查詢訂單
$sql = "
SELECT
    order_id,
    store_id,
    delivery_status
FROM ORDERS
WHERE order_id = ?
AND customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $customer_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 訂單只能在 pending 時修改
if ($order["delivery_status"] !== "pending") {
    echo json_encode([
        "error" => "Order cannot be updated after shipping"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查修改欄位
if (
    !isset($data["receiver_name"]) ||
    !isset($data["receiver_phone"]) ||
    !isset($data["receiver_address"]) ||
    !isset($data["delivery_method"])
) {
    echo json_encode([
        "error" => "Missing order information"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$receiver_name = trim($data["receiver_name"]);
$receiver_phone = trim($data["receiver_phone"]);
$receiver_address = trim($data["receiver_address"]);
$delivery_method = trim($data["delivery_method"]);

// 檢查收件資料
if (
    $receiver_name === "" ||
    $receiver_phone === "" ||
    $receiver_address === ""
) {
    echo json_encode([
        "error" => "Receiver information is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 允許的配送方式
$allowed_delivery_methods = [
    "home_delivery"
];

if (!in_array(
    $delivery_method,
    $allowed_delivery_methods,
    true
)) {
    echo json_encode([
        "error" => "Invalid delivery method"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 更新訂單
$sql = "
UPDATE ORDERS
SET
    receiver_name = ?,
    receiver_phone = ?,
    receiver_address = ?,
    delivery_method = ?,
    updated_at = NOW()
WHERE order_id = ?
AND customer_id = ?
AND store_id = ?
AND delivery_status = 'pending'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $receiver_name,
    $receiver_phone,
    $receiver_address,
    $delivery_method,
    $order_id,
    $customer_id,
    $order["store_id"]
]);

echo json_encode([
    "message" => "Order updated successfully",
    "order_id" => $order_id,
    "store_id" => (int)$order["store_id"]
], JSON_UNESCAPED_UNICODE);

?>