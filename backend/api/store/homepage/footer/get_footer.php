<?php

// Store 取得 Footer 管理設定

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/database.php";

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

// 取得 Footer 設定

$sql = "
SELECT
    contact_phone,
    contact_phone_enable,
    address,
    address_enable,
    email,
    email_enable,
    service_phone,
    service_phone_enable
FROM FOOTER_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$footer_setting = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到設定

if (!$footer_setting) {
    echo json_encode([
        "error" => "Footer setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理資料

$footer_setting["contact_phone_enable"] =
    (bool)$footer_setting["contact_phone_enable"];

$footer_setting["address_enable"] =
    (bool)$footer_setting["address_enable"];

$footer_setting["email_enable"] =
    (bool)$footer_setting["email_enable"];

$footer_setting["service_phone_enable"] =
    (bool)$footer_setting["service_phone_enable"];

// 回傳 Footer 設定
echo json_encode([
    "message" => "Footer settings retrieved successfully",
    "store_id" => $store_id,
    "footer" => $footer_setting
], JSON_UNESCAPED_UNICODE);

?>