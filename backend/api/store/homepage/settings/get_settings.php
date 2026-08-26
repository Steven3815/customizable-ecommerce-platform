<?php

// Store 取得首頁管理設定

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";


// 取得首頁區塊設定
$sql = "
SELECT
    intro_section_enable,
    banner_section_enable
FROM WEBSITE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$website_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$website_setting) {
    http_response_code(404);
    echo json_encode([
        "error" => "Website setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$website_setting["intro_section_enable"] =
    (int)$website_setting["intro_section_enable"];

$website_setting["banner_section_enable"] =
    (int)$website_setting["banner_section_enable"];

// 取得首頁商品排列設定
$sql = "
SELECT
    display_limit
FROM HOMEPAGE_PRODUCT_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$product_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product_setting) {
    http_response_code(404);
    echo json_encode([
        "error" => "Homepage product setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_setting["display_limit"] =
    (int)$product_setting["display_limit"];

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
$stmt->execute([$store_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as &$category) {
    $category["category_id"] = (int)$category["category_id"];
    $category["sort_order"] = (int)$category["sort_order"];
}

unset($category);

// 取得 Footer 設定
$sql = "
SELECT
    contact_phone_enable,
    address_enable,
    email_enable,
    service_phone_enable
FROM FOOTER_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$footer_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$footer_setting) {
    http_response_code(404);
    echo json_encode([
        "error" => "Footer setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$footer_setting["contact_phone_enable"] =
    (int)$footer_setting["contact_phone_enable"];

$footer_setting["address_enable"] =
    (int)$footer_setting["address_enable"];

$footer_setting["email_enable"] =
    (int)$footer_setting["email_enable"];

$footer_setting["service_phone_enable"] =
    (int)$footer_setting["service_phone_enable"];

// 回傳首頁管理資料
echo json_encode([
    "message" => "Homepage settings retrieved successfully",
    "store_id" => $store_id,
    "website" => $website_setting,
    "product" => $product_setting,
    "categories" => $categories,
    "footer" => $footer_setting
], JSON_UNESCAPED_UNICODE);

?>