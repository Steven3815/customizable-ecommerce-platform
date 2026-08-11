<?php

// Customer 取得單一商品詳細資料

header("Content-Type: application/json; charset=UTF-8");
require_once "../../config/database.php";

// 檢查 product_id
$product_id = $_GET["id"] ?? null;

if ($product_id === null || $product_id === "") {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !is_numeric($product_id) ||
    floor((float)$product_id) != (float)$product_id
) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$product_id = (int)$product_id;

if ($product_id <= 0) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得商品基本資料
$sql = "
SELECT
    p.product_id,
    p.product_name,
    p.description,
    p.price,
    p.stock,
    p.has_spec,
    p.status,
    c.category_id,
    c.category_name
FROM PRODUCT p
LEFT JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND c.store_id = p.store_id
WHERE p.product_id = ?
AND p.status = 'active'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        "error" => "Product not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 整理商品基本資料
$product["product_id"] = (int)$product["product_id"];

$product["category_id"] =
    $product["category_id"] !== null
        ? (int)$product["category_id"]
        : null;

$product["has_spec"] = (bool)$product["has_spec"];

// 有規格商品：價格與庫存由 PRODUCT_SPEC 管理
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
$image_sql = "
SELECT
    image_id,
    image_url,
    sort_order
FROM PRODUCT_IMAGE
WHERE product_id = ?
ORDER BY sort_order ASC, image_id ASC
";

$image_stmt = $pdo->prepare($image_sql);
$image_stmt->execute([$product_id]);

$images = $image_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as &$image) {
    $image["image_id"] = (int)$image["image_id"];
    $image["sort_order"] = (int)$image["sort_order"];
}

unset($image);

// 取得商品規格
$specs = [];

if ($product["has_spec"]) {
    $spec_sql = "
    SELECT
        spec_id,
        spec_name,
        price,
        stock
    FROM PRODUCT_SPEC
    WHERE product_id = ?
    AND status = 'active'
    ORDER BY spec_id ASC
    ";

    $spec_stmt = $pdo->prepare($spec_sql);
    $spec_stmt->execute([$product_id]);

    $specs = $spec_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($specs as &$spec) {
        $spec["spec_id"] = (int)$spec["spec_id"];
        $spec["price"] = (float)$spec["price"];
        $spec["stock"] = (int)$spec["stock"];
    }

    unset($spec);
}

// 回傳商品資料
echo json_encode([
    "product" => [
        "product_id" => $product["product_id"],
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