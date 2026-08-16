<?php

// Store 重新排列首頁商品類別

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

// 檢查 category_ids

if (
    !isset($data["category_ids"]) ||
    !is_array($data["category_ids"])
) {
    echo json_encode([
        "error" => "Category IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_ids = $data["category_ids"];

if (count($category_ids) < 1) {
    echo json_encode([
        "error" => "At least one category is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證 Category ID

$validated_category_ids = [];

foreach ($category_ids as $category_id) {

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

    if (
        in_array(
            $category_id,
            $validated_category_ids,
            true
        )
    ) {
        echo json_encode([
            "error" => "Duplicate category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $validated_category_ids[] = $category_id;
}

$pdo->beginTransaction();

try {

    // 取得目前 Store 所有未刪除 Category

    $sql = "
        SELECT
            category_id
        FROM CATEGORY
        WHERE store_id = ?
        AND status != 'deleted'
        ORDER BY sort_order ASC, category_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id
    ]);

    $existing_category_ids = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $existing_category_ids[] =
            (int)$row["category_id"];
    }

    // 確認傳入的 Category 數量正確

    if (
        count($validated_category_ids)
        !== count($existing_category_ids)
    ) {
        throw new Exception(
            "Category list is incomplete"
        );
    }

    // 確認傳入的 Category 全部屬於目前 Store

    $existing_lookup = array_flip(
        $existing_category_ids
    );

    foreach ($validated_category_ids as $category_id) {

        if (!isset($existing_lookup[$category_id])) {

            throw new Exception(
                "Category not found"
            );
        }
    }

    // 先使用暫時排序

    $temporary_offset = 1000000;

    $sql = "
        UPDATE CATEGORY
        SET
            sort_order = sort_order + ?
        WHERE store_id = ?
        AND status != 'deleted'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $temporary_offset,
        $store_id
    ]);

    // 按照 category_ids 順序重新設定 sort_order

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

    foreach (
        $validated_category_ids
        as $index => $category_id
    ) {

        $sort_order = $index + 1;

        $stmt->execute([
            $sort_order,
            $category_id,
            $store_id
        ]);
    }

    $pdo->commit();

    // 回傳新的排序

    $sql = "
        SELECT
            category_id,
            category_name,
            sort_order
        FROM CATEGORY
        WHERE store_id = ?
        AND status != 'deleted'
        ORDER BY sort_order ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id
    ]);

    $categories =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "message" =>
            "Categories reordered successfully",
        "categories" =>
            $categories
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>