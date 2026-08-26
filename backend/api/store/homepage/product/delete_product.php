<?php

// Store 刪除商品

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 product_id
if (
    !isset($data["product_id"]) ||
    $data["product_id"] === ""
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = $data["product_id"];

// 驗證 product_id
if (
    !is_numeric($product_id) ||
    floor((float)$product_id)
    != (float)$product_id
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = (int)$product_id;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $pdo->beginTransaction();

    $sql = "
        SELECT
            product_id,
            store_id,
            category_id,
            product_name,
            sort_order,
            status
        FROM PRODUCT
        WHERE product_id = ?
        AND store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {

        $pdo->rollBack();

        http_response_code(404);
        echo json_encode([
            "error" => "Product not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category_id = (int)$product["category_id"];

    // Soft Delete
    $sql = "
        UPDATE PRODUCT
        SET
            status = 'deleted',
            updated_at = NOW()
        WHERE product_id = ?
        AND store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);

    // 取得該 Category 剩餘的 active 商品 依照原本排序重新排列
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
    $remaining_products = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
        $remaining_products
        as $index => $remaining_product_id
    ) {

        $sort_order = $index + 1;

        $stmt->execute([
            $sort_order,
            (int)$remaining_product_id,
            $store_id,
            $category_id
        ]);
    }

    $pdo->commit();

    // 回傳結果
    echo json_encode([
        "message" => "Product deleted successfully",
        "product" => [
            "product_id" => (int)$product["product_id"],
            "store_id" => $store_id,
            "category_id" => $category_id,
            "product_name" => $product["product_name"],
            "sort_order" => $product["sort_order"] !== null
                ? (int)$product["sort_order"]
                : null,
            "status" => "deleted"
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    // 發生錯誤時 Rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>