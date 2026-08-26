<?php

// Store 取得單一商品詳細資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/store_auth.php";

// 檢查 Product ID
if (
    !isset($_GET["product_id"]) ||
    $_GET["product_id"] === ""
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !is_numeric($_GET["product_id"]) ||
    floor((float)$_GET["product_id"]) != (float)$_GET["product_id"]
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$product_id = (int)$_GET["product_id"];

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查商品是否屬於目前 Store
$sql = "
SELECT
    p.product_id,
    p.store_id,
    p.category_id,
    c.category_name,
    p.product_name,
    p.description,
    p.price,
    p.stock,
    p.has_spec,
    p.status,
    p.created_at,
    p.updated_at
FROM PRODUCT p
LEFT JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND c.store_id = p.store_id
WHERE p.product_id = ?
AND p.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $product_id,
    $store_id
]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// 商品不存在或不屬於目前 Store
if (!$product) {
    http_response_code(404);
    echo json_encode([
        "error" => "Product not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 整理基本資料
$product["product_id"] = (int)$product["product_id"];
$product["store_id"] = (int)$product["store_id"];

$product["category_id"] =
    $product["category_id"] !== null
        ? (int)$product["category_id"]
        : null;

$product["has_spec"] = (bool)$product["has_spec"];

// 處理商品價格與庫存
// 沒有規格：PRODUCT.price & PRODUCT.stock
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
ORDER BY sort_order ASC, image_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as &$image) {

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
        stock,
        status,
        created_at,
        updated_at
    FROM PRODUCT_SPEC
    WHERE product_id = ?
    ORDER BY spec_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    $specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($specs as &$spec) {

        $spec["spec_id"] = (int)$spec["spec_id"];
        $spec["price"] = (float)$spec["price"];
        $spec["stock"] = (int)$spec["stock"];
    }

    unset($spec);
}

// 回傳
echo json_encode([
    "message" => "Product retrieved successfully",

    "store_id" => $store_id,

    "product" => [
        "product_id" => $product["product_id"],

        "category" => [
            "category_id" => $product["category_id"],
            "category_name" => $product["category_name"]
        ],

        "product_name" => $product["product_name"],
        "description" => $product["description"],
        "price" => $product["price"],
        "stock" => $product["stock"],
        "has_spec" => $product["has_spec"],
        "specs" => $specs,
        "images" => $images,
        "status" => $product["status"],
        "created_at" => $product["created_at"],
        "updated_at" => $product["updated_at"]
    ]
], JSON_UNESCAPED_UNICODE);

?>