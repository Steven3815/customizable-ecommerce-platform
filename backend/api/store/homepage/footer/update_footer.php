<?php

// Store 取得 Footer 設定

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

if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否存在
$sql = "
SELECT store_id
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

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
    footer_id,
    store_id,

    contact_phone,
    contact_phone_enable,

    address,
    address_enable,

    email,
    email_enable,

    service_phone,
    service_phone_enable,

    created_at,
    updated_at

FROM FOOTER_SETTING

WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$footer = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到設定
if (!$footer) {
    echo json_encode([
        "error" => "Footer setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理資料
$footer["footer_id"] = (int)$footer["footer_id"];
$footer["store_id"] = (int)$footer["store_id"];

$footer["contact_phone_enable"] =
    (bool)$footer["contact_phone_enable"];

$footer["address_enable"] =
    (bool)$footer["address_enable"];

$footer["email_enable"] =
    (bool)$footer["email_enable"];

$footer["service_phone_enable"] =
    (bool)$footer["service_phone_enable"];

// 回傳
echo json_encode([
    "message" => "Footer settings retrieved successfully",
    "store_id" => $store_id,
    "footer" => $footer
], JSON_UNESCAPED_UNICODE);

?>