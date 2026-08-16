<?php

// Store 重新排列商品

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/database.php";

session_start();

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

if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 JSON

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 category_id

if (
    !isset($data["category_id"]) ||
    $data["category_id"] === ""
) {
    echo json_encode([
        "error" => "Category ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_id = $data["category_id"];

if (
    !is_numeric($category_id) ||
    floor((float)$category_id) != (float)$category_id
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

// 取得商品 ID

if (
    !isset($data["product_ids"]) ||
    !is_array($data["product_ids"])
) {
    echo json_encode([
        "error" => "Product IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_ids = $data["product_ids"];

try {

    $pdo->beginTransaction();

    // 確認 Category 存在

    $sql = "
        SELECT
            category_id
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

    if (!$stmt->fetch()) {

        throw new Exception(
            "Category not found"
        );
    }

    // 取得目前所有 active 商品

    $sql = "
        SELECT
            product_id
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

    $existing_product_ids = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $existing_product_ids[] =
            (int)$row["product_id"];
    }

    // 目前沒有商品

    if (count($existing_product_ids) === 0) {

        echo json_encode([
            "message" => "目前無有效商品",
            "category_id" => $category_id,
            "products" => []
        ], JSON_UNESCAPED_UNICODE);

        $pdo->commit();

        exit;
    }

    // 至少需要一個商品

    if (count($product_ids) < 1) {

        throw new Exception(
            "At least one product is required"
        );
    }

    // 商品數量必須完全一致

    if (
        count($product_ids)
        !== count($existing_product_ids)
    ) {
        throw new Exception(
            "Product list is incomplete"
        );
    }

    // 驗證 Product ID

    $validated_product_ids = [];

    foreach ($product_ids as $product_id) {

        if (
            !is_numeric($product_id) ||
            floor((float)$product_id)
            != (float)$product_id
        ) {
            throw new Exception(
                "Invalid product ID"
            );
        }

        $product_id = (int)$product_id;

        if ($product_id <= 0) {
            throw new Exception(
                "Invalid product ID"
            );
        }

        // 防止重複商品

        if (
            in_array(
                $product_id,
                $validated_product_ids,
                true
            )
        ) {
            throw new Exception(
                "Duplicate product ID"
            );
        }

        $validated_product_ids[] =
            $product_id;
    }

    // 建立目前商品 ID 對照表

    $existing_lookup =
        array_flip($existing_product_ids);

    // 確認商品全部屬於目前 Store 和 Category

    foreach ($validated_product_ids as $product_id) {

        if (!isset($existing_lookup[$product_id])) {

            throw new Exception(
                "Product not found"
            );
        }
    }

    // 暫時提高 sort_order
    // 避免重新排序時產生重複排序值

    $temporary_offset = 1000000;

    $sql = "
        UPDATE PRODUCT
        SET
            sort_order = sort_order + ?
        WHERE store_id = ?
        AND category_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $temporary_offset,
        $store_id,
        $category_id
    ]);

    // 重新設定 sort_order

    $sql = "
        UPDATE PRODUCT
        SET
            sort_order = ?,
            updated_at = NOW()
        WHERE product_id = ?
        AND store_id = ?
        AND category_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    foreach (
        $validated_product_ids
        as $index => $product_id
    ) {

        $sort_order = $index + 1;

        $stmt->execute([
            $sort_order,
            $product_id,
            $store_id,
            $category_id
        ]);
    }

    $pdo->commit();

    // 取得新的排序結果

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

    $products =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {

        $product["product_id"] =
            (int)$product["product_id"];

        $product["store_id"] =
            (int)$product["store_id"];

        $product["category_id"] =
            (int)$product["category_id"];

        $product["sort_order"] =
            $product["sort_order"] !== null
                ? (int)$product["sort_order"]
                : null;
    }

    unset($product);

    echo json_encode([
        "message" =>
            "Products reordered successfully",

        "category_id" =>
            $category_id,

        "products" =>
            $products
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>