<?php

// Customer 商品列表

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得搜尋條件
$category_id = $_GET["category_id"] ?? null;
$store_id = $_GET["store_id"] ?? null;
$sort = $_GET["sort"] ?? "asc";
$keyword = trim($_GET["keyword"] ?? "");

// 檢查 Store ID
if (
    $store_id === null ||
    $store_id === ""
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

// 檢查 Store
$sql = "
SELECT
    store_id,
    store_name,
    status
FROM STORE
WHERE store_id = ?
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

// Store 必須是 active
if ($store["status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Category ID
if (
    $category_id === null ||
    $category_id === ""
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Category ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    !is_numeric($category_id) ||
    floor((float)$category_id)
        != (float)$category_id ||
    (int)$category_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid category ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_id = (int)$category_id;

// 檢查 Category
$sql = "
SELECT
    category_id,
    category_name
FROM CATEGORY
WHERE category_id = ?
AND store_id = ?
AND status = 'active'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $category_id,
    $store_id
]);

$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(403);
    echo json_encode([
        "error" => "Category not found or inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得首頁商品設定
$sql = "
SELECT
    display_limit
FROM HOMEPAGE_PRODUCT_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);

$homepage_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$homepage_setting) {
    http_response_code(404);
    echo json_encode([
        "error" => "Homepage product setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$display_limit = (int)$homepage_setting["display_limit"];

if ($display_limit <= 0) {
    $display_limit = 4;
}

// 檢查排序方式
if (
    !in_array(
        $sort,
        ["asc", "desc"],
        true
    )
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid sort option"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 建立 WHERE 條件
$where = [
    "p.store_id = ?",
    "p.status = 'active'",
    "c.status = 'active'",
    "p.category_id = ?"
];

$params = [
    $store_id,
    $category_id
];

// 關鍵字搜尋
if ($keyword !== "") {

    $where[] = "
        (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )
    ";

    $search_keyword = "%" . $keyword . "%";

    $params[] = $search_keyword;
    $params[] = $search_keyword;
}

$where_sql =
    implode(
        " AND ",
        $where
    );

$order =
    $sort === "asc"
        ? "ASC"
        : "DESC";

// 取得商品
// 一個 PRODUCT 只顯示一筆
// 有規格：使用 active PRODUCT_SPEC 的最低價格
// 無規格：使用 PRODUCT.price
$sql = "
SELECT
    p.product_id,
    p.store_id,
    p.category_id,
    c.category_name,
    p.product_name,
    p.description,
    p.price,
    p.has_spec,

    (
        SELECT
            MIN(ps.price)
        FROM PRODUCT_SPEC ps
        WHERE ps.product_id = p.product_id
        AND ps.store_id = p.store_id
        AND ps.status = 'active'
    ) AS min_spec_price,

    (
        SELECT
            pi.image_url
        FROM PRODUCT_IMAGE pi
        WHERE pi.product_id = p.product_id
        AND pi.store_id = p.store_id
        ORDER BY
            pi.sort_order ASC,
            pi.image_id ASC
        LIMIT 1
    ) AS main_image

FROM PRODUCT p

INNER JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND c.store_id = p.store_id
    AND c.status = 'active'

WHERE $where_sql

ORDER BY

    CASE

        WHEN p.has_spec = 1 THEN (

            SELECT
                MIN(ps2.price)

            FROM PRODUCT_SPEC ps2

            WHERE ps2.product_id = p.product_id
            AND ps2.store_id = p.store_id
            AND ps2.status = 'active'

        )

        ELSE p.price

    END $order,

    p.product_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理商品資料
foreach (
    $products as &$product
) {
    $product["product_id"] = (int)$product["product_id"];
    $product["store_id"] = (int)$product["store_id"];

    $product["category_id"] =
        $product["category_id"] !== null
            ? (int)$product["category_id"]
            : null;

    $product["price"] =
        $product["price"] !== null
            ? (float)$product["price"]
            : null;

    $product["has_spec"] = (bool)$product["has_spec"];

    $product["min_spec_price"] =
        $product["min_spec_price"] !== null
            ? (float)$product["min_spec_price"]
            : null;

    // 商品圖片
    if (
        $product["main_image"] !== null &&
        $product["main_image"] !== ""
    ) {
        $product["main_image"] = "http://localhost/ecommerce-platform/backend" . $product["main_image"];
    }

    // 顯示價格
    // 有規格：使用最低 active 規格價格
    // 無規格：使用 PRODUCT.price

    $product["display_price"] =
        $product["has_spec"]
            ? $product["min_spec_price"]
            : $product["price"];
}

unset($product);

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
$stmt->execute([$store_id]);
$footer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$footer) {
    http_response_code(404);
    echo json_encode([
        "error" => "Footer setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理 Footer 資料
$footer["footer_id"] = (int)$footer["footer_id"];
$footer["contact_phone_enable"] = (bool)$footer["contact_phone_enable"];
$footer["address_enable"] = (bool)$footer["address_enable"];
$footer["email_enable"] = (bool)$footer["email_enable"];
$footer["service_phone_enable"] = (bool)$footer["service_phone_enable"];

// 回傳
echo json_encode([
    "message" => "Products retrieved successfully",
    "store_id" => $store_id,
    "store_name" => $store["store_name"],
    "category_id" => $category_id,
    "category_name" => $category["category_name"],
    "display_limit" => $display_limit,
    "keyword" => $keyword,
    "sort" => $sort,
    "products" => $products,
    "footer" => $footer

], JSON_UNESCAPED_UNICODE);

?>