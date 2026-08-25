<?php

// Store 回覆客服案件

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";

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
if (
    !isset($data["service_id"]) ||
    !isset($data["admin_reply"])
) {
    echo json_encode([
        "error" => "Service ID and reply are required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$service_id = $data["service_id"];
$admin_reply = trim($data["admin_reply"]);

// 檢查 Service ID
if (
    !is_numeric($service_id) ||
    floor((float)$service_id) != (float)$service_id ||
    (int)$service_id <= 0
) {
    echo json_encode([
        "error" => "Invalid service ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$service_id = (int)$service_id;

// 檢查回覆內容
if ($admin_reply === "") {
    echo json_encode([
        "error" => "Reply content is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查回覆長度
if (mb_strlen($admin_reply, "UTF-8") > 300) {
    echo json_encode([
        "error" => "Reply must not exceed 300 characters"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得客服案件
$sql = "
SELECT
    service_id,
    store_id,
    status
FROM CUSTOMER_SERVICE
WHERE service_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $service_id,
    $store_id
]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

// 客服案件不存在
if (!$service) {
    echo json_encode([
        "error" => "Service not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 已處理案件不可修改
if ($service["status"] === "resolved") {
    echo json_encode([
        "error" => "Resolved service cannot be modified"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 更新回覆並將狀態改為 resolved
$sql = "
UPDATE CUSTOMER_SERVICE
SET
    admin_reply = ?,
    status = 'resolved',
    updated_at = NOW()
WHERE service_id = ?
AND store_id = ?
AND status = 'pending'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $admin_reply,
    $service_id,
    $store_id
]);

// 確認更新成功
if ($stmt->rowCount() !== 1) {
    echo json_encode([
        "error" => "Service could not be updated"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 回傳
echo json_encode([
    "message" => "Service reply submitted successfully",
    "service_id" => $service_id,
    "status" => "resolved",
    "admin_reply" => $admin_reply
], JSON_UNESCAPED_UNICODE);

?>