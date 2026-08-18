<?php

// Customer 首頁

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

session_start();

// 取得 Customer Session
$customer_id = null;

if (
    isset($_SESSION["customer_id"]) &&
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "customer"
) {
    $customer_id = (int)$_SESSION["customer_id"];

    // 檢查 Customer ID
    if ($customer_id <= 0) {
        echo json_encode([
            "error" => "Invalid customer ID"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 檢查 Customer 是否存在
    $sql = "
    SELECT
        customer_id
    FROM CUSTOMER
    WHERE customer_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id
    ]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode([
            "error" => "Customer not found"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 取得 Store ID
$store_id = $_GET["store_id"] ?? null;

// 檢查 Store ID
if (
    $store_id === null ||
    $store_id === "" ||
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$store_id = (int)$store_id;

// 檢查 Store
$sql = "
SELECT
    store_id,
    store_name,
    store_url,
    status
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

// 檢查 Store 是否啟用
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得 Store Setting
$sql = "
SELECT
    store_status,
    store_mode
FROM STORE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id
]);

$store_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store_setting) {
    echo json_encode([
        "error" => "Store setting not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得 Website Setting
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

// 取得 Banner
$sql = "
SELECT
    banner_id,
    default_banner_id,
    image_url,
    title,
    description,
    sort_order
FROM PROMOTION_BANNER
WHERE store_id = ?
AND status = 'active'
ORDER BY
    sort_order ASC,
    banner_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id
]);

$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($banners as &$banner) {
    $banner["banner_id"] = (int)$banner["banner_id"];

    $banner["default_banner_id"] =
        $banner["default_banner_id"] !== null
            ? (int)$banner["default_banner_id"]
            : null;

    $banner["sort_order"] = (int)$banner["sort_order"];
}

unset($banner);

// 取得 Slider
$sql = "
SELECT
    image_id,
    image_url,
    title,
    sort_order
FROM SLIDER_IMAGE
WHERE store_id = ?
AND status = 'active'
ORDER BY
    sort_order ASC,
    image_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id
]);

$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sliders as &$slider) {
    $slider["image_id"] = (int)$slider["image_id"];
    $slider["sort_order"] = (int)$slider["sort_order"];
}

unset($slider);

// 取得首頁商品設定
$sql = "
SELECT
    setting_id,
    display_limit
FROM HOMEPAGE_PRODUCT_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id
]);

$homepage_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$homepage_setting) {
    echo json_encode([
        "error" => "Homepage product setting not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$display_limit = (int)$homepage_setting["display_limit"];

if ($display_limit <= 0) {
    $display_limit = 4;
}

// 取得 Category
$sql = "
SELECT
    category_id,
    category_name,
    sort_order
FROM CATEGORY
WHERE store_id = ?
AND status = 'active'
ORDER BY
    sort_order ASC,
    category_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id
]);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$homepage_products = [];

// 取得每個 Category 的商品
foreach ($categories as $category) {
    $category_id = (int)$category["category_id"];

    $sql = "
    SELECT
        p.product_id,
        p.category_id,
        p.product_name,
        p.description,
        p.price,
        p.has_spec,
        p.sort_order,

        (
            SELECT MIN(ps.price)
            FROM PRODUCT_SPEC ps
            WHERE ps.product_id = p.product_id
            AND ps.store_id = p.store_id
            AND ps.status = 'active'
        ) AS min_spec_price,

        (
            SELECT pi.image_url
            FROM PRODUCT_IMAGE pi
            WHERE pi.product_id = p.product_id
            AND pi.store_id = p.store_id
            ORDER BY
                pi.sort_order ASC,
                pi.image_id ASC
            LIMIT 1
        ) AS main_image

    FROM PRODUCT p
    WHERE p.store_id = ?
    AND p.category_id = ?
    AND p.status = 'active'
    ORDER BY
        p.sort_order ASC,
        p.product_id DESC
    LIMIT $display_limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $category_id
    ]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {
        $product["product_id"] = (int)$product["product_id"];
        $product["category_id"] = (int)$product["category_id"];
        $product["sort_order"] = (int)$product["sort_order"];

        $product["price"] =
            $product["price"] !== null
                ? (float)$product["price"]
                : null;

        $product["has_spec"] = (bool)$product["has_spec"];

        $product["min_spec_price"] =
            $product["min_spec_price"] !== null
                ? (float)$product["min_spec_price"]
                : null;

        $product["display_price"] =
            $product["has_spec"]
                ? $product["min_spec_price"]
                : $product["price"];
    }

    unset($product);

    $homepage_products[] = [
        "category_id" => $category_id,
        "category_name" => $category["category_name"],
        "sort_order" => (int)$category["sort_order"],
        "products" => $products
    ];
}

// 取得 Footer
$sql = "
SELECT
    footer_id,
    contact_phone,
    address,
    email,
    service_phone,
    contact_phone_enable,
    address_enable,
    email_enable,
    service_phone_enable
FROM FOOTER_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id
]);

$footer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$footer) {
    echo json_encode([
        "error" => "Footer setting not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 整理 Footer 資料
$footer["footer_id"] = (int)$footer["footer_id"];

$footer["contact_phone_enable"] =
    (bool)$footer["contact_phone_enable"];

$footer["address_enable"] =
    (bool)$footer["address_enable"];

$footer["email_enable"] =
    (bool)$footer["email_enable"];

$footer["service_phone_enable"] =
    (bool)$footer["service_phone_enable"];

// 回傳首頁資料
echo json_encode([
    "customer_id" => $customer_id,

    "store" => [
        "store_id" => (int)$store["store_id"],
        "store_name" => $store["store_name"],
        "store_url" => $store["store_url"]
    ],

    "store_setting" => [
        "store_status" => $store_setting["store_status"],
        "store_mode" => $store_setting["store_mode"]
    ],

    "website_setting" => [
        "intro_section_enable" =>
            (bool)$website_setting["intro_section_enable"],

        "banner_section_enable" =>
            (bool)$website_setting["banner_section_enable"]
    ],

    "banners" => $banners,

    "sliders" => $sliders,

    "homepage_product_setting" => [
        "display_limit" => $display_limit
    ],

    "categories" => $homepage_products,

    "footer" => $footer
], JSON_UNESCAPED_UNICODE);

?>