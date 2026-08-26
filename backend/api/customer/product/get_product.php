<?php

// Customer 取得單一商品詳細資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得 Product ID, Store ID
$product_id = $_GET["id"] ?? null;
$store_id = $_GET["store_id"] ?? null;

// 檢查 Product ID
if (
    $product_id === null ||
    $product_id === ""
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    !is_numeric($product_id) ||
    floor((float)$product_id)
        != (float)$product_id ||
    (int)$product_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = (int)$product_id;

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
    floor((float)$store_id)
        != (float)$store_id ||
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


// 取得商品基本資料
$sql = "
SELECT
    p.product_id,
    p.store_id,
    p.product_name,
    p.description,
    p.price,
    p.stock,
    p.has_spec,
    p.status,

    c.category_id,
    c.category_name

FROM PRODUCT p

INNER JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND c.store_id = p.store_id
    AND c.status = 'active'

WHERE p.product_id = ?
AND p.store_id = ?
AND p.status = 'active'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $product_id,
    $store_id
]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);


// 商品不存在或商品 inactive或 Category inactive
if (!$product) {
    http_response_code(404);
    echo json_encode([
        "error" => "Product not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// 整理商品資料
$product["product_id"] = (int)$product["product_id"];

$product["store_id"] = (int)$product["store_id"];

$product["category_id"] =
    $product["category_id"] !== null
        ? (int)$product["category_id"]
        : null;

$product["has_spec"] = (bool)$product["has_spec"];


// 商品價格與庫存
// 無規格：PRODUCT.price, PRODUCT.stock
// 有規格：實際價格與庫存由 PRODUCT_SPEC 管理
if ($product["has_spec"]) {

    $product["price"] = null;
    $product["stock"] = null;

} else {

    $product["price"] =
        $product["price"] !== null
            ? (float)$product["price"]
            : null;

    $product["stock"] =
        $product["stock"] !== null
            ? (int)$product["stock"]
            : null;
}

// 取得商品圖片
$sql = "
SELECT
    image_id,
    image_url,
    sort_order
FROM PRODUCT_IMAGE
WHERE product_id = ?
AND store_id = ?
ORDER BY
    sort_order ASC,
    image_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $product_id,
    $store_id
]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach (
    $images as &$image
) {
    $image["image_id"] = (int)$image["image_id"];
    $image["sort_order"] = (int)$image["sort_order"];
}

unset($image);


// 取得商品規格
$specs = [];

if ($product["has_spec"]) {

    $sql = "
    SELECT
        spec_id,
        spec_name,
        price,
        stock
    FROM PRODUCT_SPEC
    WHERE product_id = ?
    AND store_id = ?
    AND status = 'active'
    ORDER BY
        spec_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);
    $specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach (
        $specs as &$spec
    ) {
        $spec["spec_id"] = (int)$spec["spec_id"];

        $spec["price"] =
            $spec["price"] !== null
                ? (float)$spec["price"]
                : null;

        $spec["stock"] = (int)$spec["stock"];
    }

    unset($spec);
}


// 回傳商品資料
echo json_encode([
    "message" => "Product retrieved successfully",
    "store_id" => $store_id,
    "store_name" => $store["store_name"],
    "product" => [
        "product_id" => $product["product_id"],
        "store_id" => $product["store_id"],
        "product_name" => $product["product_name"],
        "description" => $product["description"],
        "price" => $product["price"],
        "stock" => $product["stock"],
        "has_spec" => $product["has_spec"],
        "category" => [
            "category_id" => $product["category_id"],
            "category_name" => $product["category_name"]
        ],
        "images" => $images,
        "specs" => $specs
    ]
], JSON_UNESCAPED_UNICODE);
?>