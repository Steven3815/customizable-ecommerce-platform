<?php

// Store 取得單一商品詳細資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

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

// 檢查 Product ID
if (
    !isset($_GET["product_id"]) ||
    $_GET["product_id"] === ""
) {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證 Product ID
if (
    !is_numeric($_GET["product_id"]) ||
    floor((float)$_GET["product_id"]) != (float)$_GET["product_id"]
) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = (int)$_GET["product_id"];

if ($product_id <= 0) {
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
    p.spec_name,
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

// 商品價格
// 不論有沒有開啟規格
// 商品價格都來自 PRODUCT.price

$product["price"] =
    $product["price"] !== null
        ? (float)$product["price"]
        : null;


// 商品庫存
// 無規格：使用 PRODUCT.stock
// 有規格：實際庫存由 PRODUCT_SPEC 管理

if ($product["has_spec"]) {

    $product["stock"] = null;

} else {

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

$stmt->execute([
    $product_id
]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as &$image) {

    $image["image_id"] =
        (int)$image["image_id"];

    $image["sort_order"] =
        (int)$image["sort_order"];
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

    $stmt->execute([
        $product_id
    ]);

    $specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($specs as &$spec) {

        $spec["spec_id"] =
            (int)$spec["spec_id"];

        // PRODUCT_SPEC.price 保留
        // 目前建立 / 更新規格時
        // 由後端自動等於 PRODUCT.price

        $spec["price"] =
            $spec["price"] !== null
                ? (float)$spec["price"]
                : null;

        $spec["stock"] =
            (int)$spec["stock"];
    }

    unset($spec);
}


// 回傳
echo json_encode([
    "message" => "Product retrieved successfully",

    "store_id" => $store_id,

    "product" => [

        // 商品 ID
        "product_id" =>
            $product["product_id"],

        // 類別
        // 編輯頁只顯示，不允許修改
        "category" => [

            "category_id" =>
                $product["category_id"],

            "category_name" =>
                $product["category_name"]
        ],

        // 商品名稱
        "product_name" =>
            $product["product_name"],

        // 商品描述
        "description" =>
            $product["description"],

        // 商品價格
        // 有規格 / 無規格都使用 PRODUCT.price
        "price" =>
            $product["price"],

        // 商品庫存
        // 有規格時為 null
        // 無規格時使用 PRODUCT.stock
        "stock" =>
            $product["stock"],

        // 是否開啟規格
        "has_spec" =>
            $product["has_spec"],

        // 商品規格名稱
        // 例如：尺寸、顏色
        "spec_name" =>
            $product["spec_name"],

        // 規格詳細資料
        "specs" =>
            $specs,

        // 商品圖片
        "images" =>
            $images,

        // 商品狀態
        "status" =>
            $product["status"],

        // 建立時間
        "created_at" =>
            $product["created_at"],

        // 更新時間
        "updated_at" =>
            $product["updated_at"]
    ]

], JSON_UNESCAPED_UNICODE);

?>