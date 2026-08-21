<?php

// Store 更新訂單配送狀態
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
    status
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);

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
        "error" => "Invalid JSON data"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["order_id"]) ||
    !isset($data["delivery_status"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $data["order_id"];
$delivery_status = $data["delivery_status"];

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

// 檢查配送狀態
if (
    $delivery_status !== "shipping" &&
    $delivery_status !== "completed"
) {
    echo json_encode([
        "error" => "Invalid delivery status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查訂單是否屬於目前 Store
$sql = "
SELECT
    order_id,
    order_number,
    store_id,
    delivery_status
FROM ORDERS
WHERE order_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到訂單或訂單不屬於這間 Store
if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查配送狀態流程
if (
    ($order["delivery_status"] === "pending" &&
     $delivery_status !== "shipping") ||

    ($order["delivery_status"] === "shipping" &&
     $delivery_status !== "completed") ||

    $order["delivery_status"] === "completed"
) {
    echo json_encode([
        "error" => "Invalid delivery status transition"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新配送狀態
$sql = "
UPDATE ORDERS
SET
    delivery_status = ?,
    updated_at = NOW()
WHERE order_id = ?
AND store_id = ?
AND delivery_status = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $delivery_status,
    $order_id,
    $store_id,
    $order["delivery_status"]

]);

// 確認是否真的更新成功
if ($stmt->rowCount() !== 1) {
    echo json_encode([
        "error" => "Failed to update order"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "message" => "Order updated successfully",
    "order_id" => $order_id,
    "order_number" => $order["order_number"],
    "store_id" => $store_id,
    "delivery_status" => $delivery_status
], JSON_UNESCAPED_UNICODE);

?>