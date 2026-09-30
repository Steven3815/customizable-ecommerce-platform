<?php

// Customer 取消客服案件 只有 pending 狀態可以取消

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得 Store ID
if (!isset($_GET["store_id"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $_GET["store_id"];

// 檢查 Store ID
if (
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$store_id;

// 取得 Service ID
if (!isset($_GET["service_id"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Service ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$service_id = $_GET["service_id"];

// 檢查 Service ID
if (
    !is_numeric($service_id) ||
    floor((float)$service_id) != (float)$service_id ||
    (int)$service_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid service ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$service_id = (int)$service_id;

// 檢查 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status,
    ss.store_mode
FROM STORE s

INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    http_response_code(404);
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 必須為 active
if ($store["status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不可使用客服
if ($store["store_mode"] !== "shopping") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得客服案件
$sql = "
SELECT
    service_id,
    customer_id,
    store_id,
    status
FROM CUSTOMER_SERVICE
WHERE service_id = ?
AND customer_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $service_id,
    $customer_id,
    $store_id
]);

$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    http_response_code(404);
    echo json_encode([
        "error" => "Customer service request not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 只有 pending 可以取消
if ($service["status"] !== "pending") {
    http_response_code(400);
    echo json_encode([
        "error" => "Only pending customer service requests can be cancelled"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取消客服案件
$sql = "
UPDATE CUSTOMER_SERVICE
SET status = 'cancelled'
WHERE service_id = ?
AND customer_id = ?
AND store_id = ?
AND status = 'pending'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $service_id,
    $customer_id,
    $store_id
]);

// 確認是否更新成功
if ($stmt->rowCount() === 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Failed to cancel customer service request"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳成功
echo json_encode([
    "message" => "Customer service request cancelled successfully",
    "service" => [
        "service_id" => $service_id,
        "status" => "cancelled"
    ]
], JSON_UNESCAPED_UNICODE);

?>