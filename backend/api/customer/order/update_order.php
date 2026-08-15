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

// 檢查 Store 狀態
$sql = "
SELECT
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
    $order["store_id"]
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store setting not found"
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
    !isset($data["receiver_address"])
) {
    echo json_encode([
        "error" => "Missing order information"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$receiver_name = trim($data["receiver_name"]);
$receiver_phone = trim($data["receiver_phone"]);
$receiver_address = trim($data["receiver_address"]);

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

// 更新訂單
$sql = "
UPDATE ORDERS
SET
    receiver_name = ?,
    receiver_phone = ?,
    receiver_address = ?,
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