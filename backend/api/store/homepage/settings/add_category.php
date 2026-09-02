<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";

try {
    // 取得 JSON
    $input = json_decode(file_get_contents("php://input"), true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid JSON data"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 檢查類別名稱
    if (!isset($input["category_name"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Category name is required"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $category_name = trim($input["category_name"]);

    if ($category_name === "") {
        http_response_code(400);
        echo json_encode([
            "error" => "Category name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (mb_strlen($category_name) > 255) {
        http_response_code(400);
        echo json_encode([
            "error" => "Category name cannot exceed 255 characters"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    // 檢查目前商店是否已有相同類別名稱
    $sql = "
        SELECT category_id
        FROM CATEGORY
        WHERE store_id = ?
        AND category_name = ?
        AND status != 'deleted'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $category_name
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();

        http_response_code(409);
        echo json_encode([
            "error" => "Category name already exists"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 取得新的排序位置
    $sql = "
        SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort_order
        FROM CATEGORY
        WHERE store_id = ?
        AND status != 'deleted'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    $next_sort_order = (int)$stmt->fetchColumn();

    // 新增類別
    $sql = "
        INSERT INTO CATEGORY (
            store_id,
            category_name,
            sort_order,
            status
        )
        VALUES (?, ?, ?, 'active')
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $category_name,
        $next_sort_order
    ]);

    $category_id = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        "message" => "Category added successfully",
        "category" => [
            "category_id" => $category_id,
            "category_name" => $category_name,
            "sort_order" => $next_sort_order
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "error" => "Failed to add category"
    ], JSON_UNESCAPED_UNICODE);
}
?>