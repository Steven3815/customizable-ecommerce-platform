<?php

// Store 取得商家資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/store_auth.php";

// 取得商家資料
$sql = "
SELECT
    store_id,
    store_name,
    store_url,
    owner_name,
    email,
    phone,
    status,
    created_at,
    updated_at
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到商家
if (!$store) {
    http_response_code(404);
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 資料型態整理
$store["store_id"] = (int)$store["store_id"];

// 回傳商家資料
echo json_encode([
    "store" => $store
], JSON_UNESCAPED_UNICODE);

?>