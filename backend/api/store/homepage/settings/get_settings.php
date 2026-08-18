<?php

// Store 取得首頁管理設定

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

// 取得首頁區塊設定

$sql = "
SELECT
    intro_section_enable,
    banner_section_enable
FROM WEBSITE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$website_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$website_setting) {
    echo json_encode([
        "error" => "Website setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得首頁商品排列設定

$sql = "
SELECT
    display_limit
FROM HOMEPAGE_PRODUCT_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$product_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product_setting) {
    echo json_encode([
        "error" => "Homepage product setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得商品類別

$sql = "
SELECT
    category_id,
    category_name,
    sort_order
FROM CATEGORY
WHERE store_id = ?
AND status != 'deleted'
ORDER BY sort_order ASC, category_id ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 回傳首頁管理資料
echo json_encode([
    "message" => "Homepage settings retrieved successfully",
    "store_id" => $store_id,
    "website" => $website_setting,
    "product" => $product_setting,
    "categories" => $categories
], JSON_UNESCAPED_UNICODE);

?>