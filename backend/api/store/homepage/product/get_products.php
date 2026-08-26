<?php

// Store 取得商品管理資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";

$has_category_id =
    isset($_GET["category_id"]) &&
    $_GET["category_id"] !== "";

if ($has_category_id) {

    $category_id = $_GET["category_id"];

    if (
        !is_numeric($category_id) ||
        floor((float)$category_id)
        != (float)$category_id
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category_id = (int)$category_id;

    if ($category_id <= 0) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

} else {

    $category_id = null;
}

try {

    // 沒有指定 category_id → 使用第一個 active 類別
    if ($category_id === null) {

        $sql = "
            SELECT
                category_id
            FROM CATEGORY
            WHERE store_id = ?
            AND status = 'active'
            ORDER BY
                sort_order ASC,
                category_id ASC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$store_id]);
        $default_category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$default_category) {

            echo json_encode([
                "message" => "目前無有效類別",
                "store_id" => $store_id,
                "category" => null
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $category_id = (int)$default_category["category_id"];
    }

    // 取得類別
    $sql = "
        SELECT
            category_id,
            store_id,
            category_name,
            sort_order,
            status
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
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {

        http_response_code(404);
        echo json_encode([
            "error" => "Category not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category["category_id"] = (int)$category["category_id"];
    $category["store_id"] = (int)$category["store_id"];
    $category["sort_order"] =
        $category["sort_order"] !== null
            ? (int)$category["sort_order"]
            : null;

    // 取得商品
    $sql = "
        SELECT
            product_id,
            store_id,
            category_id,
            product_name,
            sort_order,
            status
        FROM PRODUCT
        WHERE store_id = ?
        AND category_id = ?
        AND status = 'active'
        ORDER BY
            sort_order ASC,
            product_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $category_id
    ]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {
        $product["product_id"] = (int)$product["product_id"];
        $product["store_id"] = (int)$product["store_id"];
        $product["category_id"] = (int)$product["category_id"];
        $product["sort_order"] =
            $product["sort_order"] !== null
                ? (int)$product["sort_order"]
                : null;
    }

    unset($product);

    $category["products"] = $products;

    echo json_encode([
        "message" => "Product management data retrieved successfully",
        "store_id" => $store_id,
        "category" => $category
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>