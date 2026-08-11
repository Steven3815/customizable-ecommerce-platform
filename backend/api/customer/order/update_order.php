<?php

// 更新訂單
header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Customer Session
if (!isset($_SESSION["customer_id"])) {
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

// 檢查必要欄位
if (
    !isset($data["order_id"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$order_id = $data["order_id"];

$sql = "
SELECT
    order_id,
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

$receiver_name = $data["receiver_name"];
$receiver_phone = $data["receiver_phone"];
$receiver_address = $data["receiver_address"];
$delivery_method = $data["delivery_method"];

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
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $receiver_name,
    $receiver_phone,
    $receiver_address,
    $delivery_method,
    $order_id,
    $customer_id
]);

echo json_encode([
    "message" => "Order updated successfully",
    "order_id" => (int)$order_id
], JSON_UNESCAPED_UNICODE);

?>