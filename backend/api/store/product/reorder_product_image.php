<?php

// Store 重新排列商品圖片

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

// 取得 product_id
if (
    !isset($data["product_id"]) ||
    $data["product_id"] === ""
) {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = $data["product_id"];

// 檢查 product_id
if (
    !is_numeric($product_id) ||
    floor((float)$product_id) != (float)$product_id
) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = (int)$product_id;

if ($product_id <= 0) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 image_ids
if (
    !isset($data["image_ids"]) ||
    !is_array($data["image_ids"])
) {
    echo json_encode([
        "error" => "Image IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_ids = $data["image_ids"];

try {

    // 開始 Transaction
    $pdo->beginTransaction();

    $sql = "
        SELECT
            product_id,
            store_id,
            product_name,
            status
        FROM PRODUCT
        WHERE product_id = ?
        AND store_id = ?
        AND status != 'deleted'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {

        throw new Exception(
            "Product not found"
        );
    }

    // 取得目前所有圖片
    $sql = "
        SELECT
            image_id
        FROM PRODUCT_IMAGE
        WHERE product_id = ?
        AND store_id = ?
        ORDER BY
            sort_order ASC,
            image_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);

    $existing_image_ids = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $existing_image_ids[] = (int)$row["image_id"];
    }

    // 目前沒有圖片
    if (count($existing_image_ids) === 0) {

        echo json_encode([
            "message" => "目前無商品圖片",
            "store_id" => $store_id,
            "product_id" => $product_id,
            "images" => []
        ], JSON_UNESCAPED_UNICODE);

        $pdo->commit();

        exit;
    }

    // 至少需要一張圖片
    if (count($image_ids) < 1) {

        throw new Exception("At least one image is required");
    }

    // 圖片數量必須完全一致
    if (
        count($image_ids)
        !== count($existing_image_ids)
    ) {

        throw new Exception("Image list is incomplete");
    }

    // 驗證 Image ID
    $validated_image_ids = [];

    foreach ($image_ids as $image_id) {

        if (
            !is_numeric($image_id) ||
            floor((float)$image_id)
            != (float)$image_id
        ) {
            throw new Exception("Invalid image ID");
        }

        $image_id = (int)$image_id;

        if ($image_id <= 0) {
            throw new Exception("Invalid image ID");
        }

        // 防止重複圖片
        if (
            in_array(
                $image_id,
                $validated_image_ids,
                true
            )
        ) {
            throw new Exception("Duplicate image ID");
        }

        $validated_image_ids[] = $image_id;
    }

    // 建立目前圖片 ID 對照表
    $existing_lookup = array_flip($existing_image_ids);

    // 確認所有圖片都屬於目前 Product和 Store
    foreach ($validated_image_ids as $image_id) {

        if (!isset($existing_lookup[$image_id])) {

            throw new Exception("Image not found");
        }
    }

    // 暫時提高 sort_order
    // 避免重新排序時產生重複排序值
    $temporary_offset = 1000000;

    $sql = "
        UPDATE PRODUCT_IMAGE
        SET
            sort_order = sort_order + ?
        WHERE product_id = ?
        AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $temporary_offset,
        $product_id,
        $store_id
    ]);

    // 重新設定 sort_order
    $sql = "
        UPDATE PRODUCT_IMAGE
        SET
            sort_order = ?
        WHERE image_id = ?
        AND product_id = ?
        AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    foreach (
        $validated_image_ids as $index => $image_id
    ) {

        $sort_order = $index + 1;

        $stmt->execute([
            $sort_order,
            $image_id,
            $product_id,
            $store_id
        ]);
    }

    // 完成 Transaction
    $pdo->commit();

    // 取得新的圖片排序結果
    $sql = "
        SELECT
            image_id,
            product_id,
            store_id,
            image_url,
            sort_order
        FROM PRODUCT_IMAGE
        WHERE product_id = ?
        AND store_id = ?
        ORDER BY
            sort_order ASC,
            image_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理資料型別
    foreach ($images as &$image) {

        $image["image_id"] = (int)$image["image_id"];
        $image["product_id"] = (int)$image["product_id"];
        $image["store_id"] = (int)$image["store_id"];
        $image["sort_order"] =
            $image["sort_order"] !== null
                ? (int)$image["sort_order"]
                : null;
    }

    unset($image);

    // 回傳
    echo json_encode([

        "message" => "Product images reordered successfully",
        "store_id" => $store_id,
        "product_id" => $product_id,
        "product_name" => $product["product_name"],
        "images" => $images
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