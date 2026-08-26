<?php

// Customer 取得 Store

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";
require_once "../../config/database.php";

// 取得 store_id
$store_id = $_GET["store_id"] ?? null;

// 檢查 store_id
if (
    $store_id === null ||
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

// 取得 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.store_url,
    s.status AS store_status,
    ss.store_mode
FROM STORE s
INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id
WHERE s.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

// Store 不存在
if (!$store) {
    http_response_code(404);

    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 停用
if ($store["store_status"] !== "active") {
    http_response_code(403);

    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "store" => $store
], JSON_UNESCAPED_UNICODE);

?>