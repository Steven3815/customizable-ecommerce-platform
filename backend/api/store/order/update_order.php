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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

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


// 檢查配送狀態

if ($delivery_status !== "shipping") {

    echo json_encode([
        "error" => "Invalid delivery status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查訂單是否屬於這間 Store
$sql = "
SELECT DISTINCT
    o.order_id,
    o.delivery_status
FROM ORDERS o

JOIN ORDER_ITEM oi
ON o.order_id = oi.order_id

JOIN PRODUCT p
ON oi.product_id = p.product_id

WHERE o.order_id = ?
AND p.store_id = ?
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

// 檢查目前配送狀態
if ($order["delivery_status"] !== "pending") {
    echo json_encode([
        "error" => "Order cannot be updated"
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
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $delivery_status,
    $order_id
]);

echo json_encode([
    "message" => "Order updated successfully",
    "order_id" => (int)$order_id,
    "delivery_status" => $delivery_status
], JSON_UNESCAPED_UNICODE);

?>
