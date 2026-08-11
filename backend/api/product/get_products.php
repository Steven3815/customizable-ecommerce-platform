<?php

// Customer 商品列表

header("Content-Type: application/json; charset=UTF-8");
require_once "../../config/database.php";

// 取得搜尋條件
$category_id = $_GET["category_id"] ?? null;
$sort = $_GET["sort"] ?? "asc";

// 檢查 category_id
if ($category_id !== null && $category_id !== "") {
    if (
        !is_numeric($category_id) ||
        floor((float)$category_id) != (float)$category_id ||
        (int)$category_id <= 0
    ) {
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $category_id = (int)$category_id;
}

// 檢查排序方式
if (!in_array($sort, ["asc", "desc"], true)) {
    echo json_encode([
        "error" => "Invalid sort option"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$where = [
    "p.status = 'active'"
];

$params = [];

// 分類篩選
if ($category_id !== null && $category_id !== "") {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
}

$where_sql = implode(" AND ", $where);
$order = $sort === "asc" ? "ASC" : "DESC";

// 取得商品
$sql = "
SELECT
    p.product_id,
    p.category_id,
    p.product_name,
    p.description,
    p.price,
    p.has_spec,

    (
        SELECT MIN(ps.price)
        FROM PRODUCT_SPEC ps
        WHERE ps.product_id = p.product_id
        AND ps.status = 'active'
    ) AS min_spec_price,

    (
        SELECT pi.image_url
        FROM PRODUCT_IMAGE pi
        WHERE pi.product_id = p.product_id
        ORDER BY pi.sort_order ASC, pi.image_id ASC
        LIMIT 1
    ) AS main_image

FROM PRODUCT p

WHERE $where_sql

ORDER BY
    CASE
        WHEN p.has_spec = 1 THEN (
            SELECT MIN(ps2.price)
            FROM PRODUCT_SPEC ps2
            WHERE ps2.product_id = p.product_id
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
foreach ($products as &$product) {
    $product["product_id"] = (int)$product["product_id"];
    $product["category_id"] = (int)$product["category_id"];

    $product["price"] =
        $product["price"] !== null
            ? (float)$product["price"]
            : null;

    $product["has_spec"] = (bool)$product["has_spec"];

    $product["min_spec_price"] =
        $product["min_spec_price"] !== null
            ? (float)$product["min_spec_price"]
            : null;

    // 無規格：使用 PRODUCT.price
    // 有規格：使用 active 規格中的最低價格
    $product["display_price"] =
        $product["has_spec"]
            ? $product["min_spec_price"]
            : $product["price"];
}

unset($product);

// 回傳
echo json_encode([
    "category_id" => $category_id,
    "sort" => $sort,
    "products" => $products
], JSON_UNESCAPED_UNICODE);

?>