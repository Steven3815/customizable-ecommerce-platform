<?php

// Store 處理退款

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/store_auth.php";

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON 格式
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["refund_id"]) ||
    !isset($data["refund_status"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$refund_id = $data["refund_id"];
$refund_status = $data["refund_status"];
$admin_reply = $data["admin_reply"] ?? null;

// 檢查 Refund ID
if (
    !is_numeric($refund_id) ||
    floor((float)$refund_id) != (float)$refund_id ||
    (int)$refund_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid refund ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$refund_id = (int)$refund_id;

// 檢查退款狀態是否合法
$allowed_status = [
    "approved",
    "rejected"
];

if (!in_array($refund_status, $allowed_status, true)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid refund status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 admin_reply
if ($admin_reply !== null) {

    if (!is_string($admin_reply)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid admin reply"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $admin_reply = trim($admin_reply);

    if (mb_strlen($admin_reply) > 1000) {
        http_response_code(400);
        echo json_encode([
            "error" => "Admin reply is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 檢查退款是否屬於目前 Store
$sql = "
SELECT
    refund_id,
    store_id,
    refund_status
FROM REFUND
WHERE refund_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $refund_id,
    $store_id
]);
$refund = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$refund) {
    http_response_code(404);
    echo json_encode([
        "error" => "Refund not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只能處理 pending
if ($refund["refund_status"] !== "pending") {
    http_response_code(409);
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
AND store_id = ?
";

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $refund_status,
        $admin_reply,
        $refund_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("Failed to update refund");
    }

    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status_code = $e->getCode();
    if ($status_code < 400 || $status_code > 599) {
        $status_code = 500;
    }
    http_response_code($status_code);
    echo json_encode([
        "error" => "Refund update failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳結果
echo json_encode([
    "message" => "Refund updated successfully",
    "store_id" => $store_id,
    "refund_id" => $refund_id,
    "refund_status" => $refund_status,
    "admin_reply" => $admin_reply
], JSON_UNESCAPED_UNICODE);

?>