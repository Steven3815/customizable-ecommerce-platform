<?php
// 商品列表頁
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

$sql = "
SELECT 
    product_id,
    product_name,
    description,
    price,
    stock,
    status
FROM PRODUCT
WHERE status = 'active'
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(
    $products,
    JSON_UNESCAPED_UNICODE
);

?>