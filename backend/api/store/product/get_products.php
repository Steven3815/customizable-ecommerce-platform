<?php

// Store 商品列表

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

// 取得搜尋條件
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = trim($_GET["search"] ?? "");
$category_id = $_GET["category_id"] ?? null;
$status = $_GET["status"] ?? null;
$stock_status = $_GET["stock_status"] ?? null;

// 檢查 page
if ($page < 1) {
    echo json_encode([
        "error" => "Invalid page"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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

// 檢查 status
$allowed_status = [
    "active",
    "hidden",
    "deleted"
];

if ($status !== null && $status !== "") {
    if (!in_array($status, $allowed_status, true)) {
        echo json_encode([
            "error" => "Invalid product status"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 檢查 stock_status
$allowed_stock_status = [
    "in_stock",
    "low",
    "out_of_stock"
];

if ($stock_status !== null && $stock_status !== "") {
    if (!in_array($stock_status, $allowed_stock_status, true)) {
        echo json_encode([
            "error" => "Invalid stock status"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 建立 WHERE
$where = [];
$params = [];

$where[] = "p.store_id = ?";
$params[] = $store_id;

// 預設不顯示 deleted
if ($status === null || $status === "") {
    $where[] = "p.status != ?";
    $params[] = "deleted";
} else {
    $where[] = "p.status = ?";
    $params[] = $status;
}

// 商品名稱搜尋
if ($search !== "") {
    $where[] = "p.product_name LIKE ?";
    $params[] = "%" . $search . "%";
}

// 類別篩選
if ($category_id !== null && $category_id !== "") {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
}

$where_sql = implode(" AND ", $where);

// 查詢總商品數
$sql = "
SELECT COUNT(*)
FROM PRODUCT p
WHERE $where_sql
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$total = (int)$stmt->fetchColumn();

$total_pages = $total > 0
    ? (int)ceil($total / $limit)
    : 0;

// 取得商品列表
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
    p.updated_at,
    CASE
        WHEN p.has_spec = 1 THEN
            COALESCE(
                (
                    SELECT SUM(ps.stock)
                    FROM PRODUCT_SPEC ps
                    WHERE ps.product_id = p.product_id
                    AND ps.status = 'active'
                ),
                0
            )
        ELSE p.stock
    END AS total_stock,
    CASE
        WHEN p.has_spec = 1 THEN
            (
                SELECT MIN(ps.price)
                FROM PRODUCT_SPEC ps
                WHERE ps.product_id = p.product_id
                AND ps.status = 'active'
            )
        ELSE p.price
    END AS display_price
FROM PRODUCT p
LEFT JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND c.store_id = p.store_id
WHERE $where_sql
";

// 庫存狀態篩選
if ($stock_status === "out_of_stock") {
    $sql .= "
    AND (
        (
            p.has_spec = 0
            AND p.stock = 0
        )
        OR
        (
            p.has_spec = 1
            AND COALESCE(
                (
                    SELECT SUM(ps.stock)
                    FROM PRODUCT_SPEC ps
                    WHERE ps.product_id = p.product_id
                    AND ps.status = 'active'
                ),
                0
            ) = 0
        )
    )
    ";
}

if ($stock_status === "low") {
    $sql .= "
    AND (
        (
            p.has_spec = 0
            AND p.stock BETWEEN 1 AND 5
        )
        OR
        (
            p.has_spec = 1
            AND COALESCE(
                (
                    SELECT SUM(ps.stock)
                    FROM PRODUCT_SPEC ps
                    WHERE ps.product_id = p.product_id
                    AND ps.status = 'active'
                ),
                0
            ) BETWEEN 1 AND 5
        )
    )
    ";
}

if ($stock_status === "in_stock") {
    $sql .= "
    AND (
        (
            p.has_spec = 0
            AND p.stock > 5
        )
        OR
        (
            p.has_spec = 1
            AND COALESCE(
                (
                    SELECT SUM(ps.stock)
                    FROM PRODUCT_SPEC ps
                    WHERE ps.product_id = p.product_id
                    AND ps.status = 'active'
                ),
                0
            ) > 5
        )
    )
    ";
}

$sql .= "
ORDER BY p.product_id DESC
LIMIT $limit
OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理商品資料
foreach ($products as &$product) {
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

    $product["stock"] =
        $product["stock"] !== null
            ? (int)$product["stock"]
            : null;

    $product["has_spec"] = (bool)$product["has_spec"];

    $product["total_stock"] =
        (int)$product["total_stock"];

    $product["display_price"] =
        $product["display_price"] !== null
            ? (float)$product["display_price"]
            : null;

    // 判斷庫存狀態
    if ($product["total_stock"] <= 0) {
        $product["stock_status"] = "out_of_stock";
    } elseif ($product["total_stock"] <= 5) {
        $product["stock_status"] = "low";
    } else {
        $product["stock_status"] = "in_stock";
    }
}

unset($product);

// 回傳
echo json_encode([
    "message" => "Products retrieved successfully",
    "store_id" => $store_id,
    "page" => $page,
    "limit" => $limit,
    "total" => $total,
    "total_pages" => $total_pages,
    "products" => $products
], JSON_UNESCAPED_UNICODE);

?>