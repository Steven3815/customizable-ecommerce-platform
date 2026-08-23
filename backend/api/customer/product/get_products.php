<?php

// Customer 商品列表

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Customer Session
if (
    !isset($_SESSION["customer_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 必須是 active
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Category ID
if (
    $category_id !== null &&
    $category_id !== ""
) {
    if (
        !is_numeric($category_id) ||
        floor((float)$category_id)
            != (float)$category_id ||
        (int)$category_id <= 0
    ) {
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category_id = (int)$category_id;

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
        echo json_encode([
            "error" => "Category not found or inactive"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 檢查排序方式
if (
    !in_array(
        $sort,
        ["asc", "desc"],
        true
    )
) {
    echo json_encode([
        "error" => "Invalid sort option"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 建立 WHERE 條件
$where = [
    "p.store_id = ?",
    "p.status = 'active'",
    "c.status = 'active'"
];

$params = [$store_id];

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

// Category 篩選
if (
    $category_id !== null &&
    $category_id !== ""
) {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
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
$products =$stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // 顯示價格
    // 有規格：使用最低 active 規格價格
    // 無規格：使用 PRODUCT.price

    $product["display_price"] =
        $product["has_spec"]
            ? $product["min_spec_price"]
            : $product["price"];
}

unset($product);

// 回傳
echo json_encode([
    "message" => "Products retrieved successfully",
    "store_id" => $store_id,
    "store_name" => $store["store_name"],
    "keyword" => $keyword,
    "category_id" => $category_id,
    "sort" => $sort,
    "products" => $products
], JSON_UNESCAPED_UNICODE);

?>