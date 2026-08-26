<?php

// Store 刪除商品類別

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

// 檢查 category_ids
if (
    !isset($data["category_ids"]) ||
    !is_array($data["category_ids"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Category IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_ids = $data["category_ids"];

// 至少一個 Category
if (count($category_ids) < 1) {
    http_response_code(400);
    echo json_encode([
        "error" => "At least one category is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證 Category ID
$validated_category_ids = [];

foreach ($category_ids as $category_id) {

    // 檢查是否為整數
    if (
        !is_numeric($category_id) ||
        floor((float)$category_id) != (float)$category_id
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category_id = (int)$category_id;

    // ID 必須大於 0
    if ($category_id <= 0) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 防止同一個 Category 重複
    if (
        in_array(
            $category_id,
            $validated_category_ids,
            true
        )
    ) {
        http_response_code(409);
        echo json_encode([
            "error" => "Duplicate category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $validated_category_ids[] = $category_id;
}

try {

    // 開始 Transaction
    $pdo->beginTransaction();

    // 確認所有 Category 都屬於目前 Store
    foreach ($validated_category_ids as $category_id) {

        $sql = "
            SELECT
                category_id
            FROM CATEGORY
            WHERE category_id = ?
            AND store_id = ?
            AND status != 'deleted'
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $category_id,
            $store_id
        ]);

        if (!$stmt->fetch()) {
            throw new Exception("Category not found", 404);
        }
    }

    // Soft Delete 同時將 sort_order 設為 NULL
    foreach ($validated_category_ids as $category_id) {

        $sql = "
            UPDATE CATEGORY
            SET
                status = 'deleted',
                sort_order = NULL,
                updated_at = NOW()
            WHERE category_id = ?
            AND store_id = ?
            AND status != 'deleted'
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $category_id,
            $store_id
        ]);
    }

    // 取得刪除後剩餘的 Category
    $sql = "
        SELECT
            category_id
        FROM CATEGORY
        WHERE store_id = ?
        AND status != 'deleted'
        ORDER BY sort_order ASC, category_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $remaining_category_ids =
        $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 重新整理 sort_order
    $sort_order = 1;

    foreach (
        $remaining_category_ids
        as $category_id
    ) {

        $sql = "
            UPDATE CATEGORY
            SET
                sort_order = ?,
                updated_at = NOW()
            WHERE category_id = ?
            AND store_id = ?
            AND status != 'deleted'
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $sort_order,
            $category_id,
            $store_id
        ]);

        $sort_order++;
    }

    $pdo->commit();

    // 取得重新排序後的 Category
    $sql = "
        SELECT
            category_id,
            category_name,
            sort_order
        FROM CATEGORY
        WHERE store_id = ?
        AND status != 'deleted'
        ORDER BY sort_order ASC, category_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 回傳
    echo json_encode([
        "message" => "Categories deleted and reordered successfully",

        "categories" => $categories
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    // 發生錯誤時 Rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status_code = $e->getCode();
    if ($status_code < 400 || $status_code > 599) {
        $status_code = 500;
    }
    http_response_code($status_code);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>