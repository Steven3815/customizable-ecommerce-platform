<?php
// 單一商品詳細頁
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

if (!isset($_GET["id"])) {
    echo json_encode([
        "error" => "Product ID is required"
    ]);
    exit;
}
$product_id = $_GET["id"];

// 取得商品基本資料 + 分類
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
WHERE p.product_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {

    echo json_encode([
        "error" => "Product not found"
    ]);

    exit;

}

// 取得商品圖片
$image_sql = "
SELECT
    image_id,
    image_url,
    sort_order
FROM PRODUCT_IMAGE
WHERE product_id = ?
ORDER BY sort_order ASC
";

$image_stmt = $pdo->prepare($image_sql);
$image_stmt->execute([$product_id]);
$product["images"] = $image_stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得商品規格
$spec_sql = "
SELECT
    spec_id,
    spec_name,
    price,
    stock
FROM PRODUCT_SPEC
WHERE product_id = ?
";

$spec_stmt = $pdo->prepare($spec_sql);
$spec_stmt->execute([$product_id]);
$product["specs"] = $spec_stmt->fetchAll(PDO::FETCH_ASSOC);

// 回傳 JSON
echo json_encode(
    $product,
    JSON_UNESCAPED_UNICODE
);

?>