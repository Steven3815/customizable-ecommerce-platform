<?php

// Store 處理退款
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
    store_id
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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (
    !isset($data["refund_id"]) ||
    !isset($data["refund_status"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$refund_id = $data["refund_id"];
$refund_status = $data["refund_status"];
$admin_reply = $data["admin_reply"] ?? null;

// 檢查退款狀態是否合法
$allowed_status = [
    "approved",
    "rejected"
];

if (!in_array($refund_status, $allowed_status, true)) {
    echo json_encode([
        "error" => "Invalid refund status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查退款是否屬於目前 Store
$sql = "
SELECT
    r.refund_id,
    r.refund_status
FROM REFUND r
JOIN ORDERS o
ON r.order_id = o.order_id
JOIN ORDER_ITEM oi
ON o.order_id = oi.order_id
JOIN PRODUCT p
ON oi.product_id = p.product_id
WHERE r.refund_id = ?
AND p.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $refund_id,
    $store_id
]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到退款
if (!$refund) {
    echo json_encode([
        "error" => "Refund not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只能處理 pending
if ($refund["refund_status"] !== "pending") {
    echo json_encode([
        "error" => "Refund has already been processed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新退款
$sql = "
UPDATE REFUND
SET
    refund_status = ?,
    admin_reply = ?,
    processed_at = NOW()
WHERE refund_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $refund_status,
    $admin_reply,
    $refund_id
]);

// 回傳結果
echo json_encode([
    "message" => "Refund updated successfully",
    "refund_id" => (int)$refund_id,
    "refund_status" => $refund_status,
    "admin_reply" => $admin_reply
], JSON_UNESCAPED_UNICODE);

?>