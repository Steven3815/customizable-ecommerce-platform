<?php

// Store 新增商品

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/database.php";

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

// 檢查 Store 是否存在
$sql = "
SELECT
    store_id
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

// 取得 category_id
if (
    !isset($_POST["category_id"]) ||
    $_POST["category_id"] === ""
) {
    echo json_encode([
        "error" => "Category ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_id = $_POST["category_id"];

// 驗證 category_id
if (
    !is_numeric($category_id) ||
    floor((float)$category_id)
    != (float)$category_id
) {
    echo json_encode([
        "error" => "Invalid category ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_id = (int)$category_id;

if ($category_id <= 0) {
    echo json_encode([
        "error" => "Invalid category ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得商品名稱
$product_name = trim(
    $_POST["product_name"] ?? ""
);

if ($product_name === "") {
    echo json_encode([
        "error" => "Product name is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (mb_strlen($product_name) > 200) {
    echo json_encode([
        "error" => "Product name is too long"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    // 確認 Category 存在、屬於目前 Store 且為 active
    $sql = "
        SELECT
            category_id,
            category_name
        FROM CATEGORY
        WHERE category_id = ?
        AND store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $category_id,
        $store_id
    ]);

    $category =
        $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode([
            "error" => "Category not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category_name =
        $category["category_name"];

    // 取得該 Category 最後一個 sort_order
    $sql = "
        SELECT
            COALESCE(MAX(sort_order), 0) + 1
            AS next_sort_order
        FROM PRODUCT
        WHERE store_id = ?
        AND category_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id,
        $category_id
    ]);

    $result =
        $stmt->fetch(PDO::FETCH_ASSOC);

    $sort_order =
        (int)$result["next_sort_order"];

    // 新增商品
    $sql = "
        INSERT INTO PRODUCT (
            store_id,
            category_id,
            product_name,
            sort_order,
            status
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            'active'
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id,
        $category_id,
        $product_name,
        $sort_order
    ]);

    $product_id =
        (int)$pdo->lastInsertId();

    echo json_encode([
        "message" =>
            "Product added successfully",

        "product" => [
            "product_id" =>
                $product_id,

            "store_id" =>
                $store_id,

            "category_id" =>
                $category_id,

            "category_name" =>
                $category_name,

            "product_name" =>
                $product_name,

            "sort_order" =>
                $sort_order,

            "status" =>
                "active"
        ]

    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>