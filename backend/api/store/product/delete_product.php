<?php

// Store 刪除商品

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

// 取得 Product ID
$product_id = $_POST["product_id"] ?? null;

if ($product_id === null || $product_id === "") {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Product ID
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

// 檢查商品是否屬於目前 Store
$sql = "
SELECT
    product_id,
    product_name,
    status
FROM PRODUCT
WHERE product_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $product_id,
    $store_id
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        "error" => "Product not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 已刪除商品不能再次刪除
if ($product["status"] === "deleted") {
    echo json_encode([
        "error" => "Product has already been deleted"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得商品圖片
$sql = "
SELECT
    image_id,
    image_url
FROM PRODUCT_IMAGE
WHERE product_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $product_id,
    $store_id
]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 開始交易
$pdo->beginTransaction();

try {

    // 1. 移除購物車中的商品
    $sql = "
    DELETE FROM CART_ITEM
    WHERE product_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $product_id,
        $store_id
    ]);

    $deleted_cart_items = $stmt->rowCount();

    // 2. 停用商品規格
    // 不刪除 PRODUCT_SPEC，保留歷史資料
    $sql = "
    UPDATE PRODUCT_SPEC
    SET
        status = 'inactive',
        updated_at = NOW()
    WHERE product_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $product_id,
        $store_id
    ]);

    $deactivated_specs = $stmt->rowCount();

    // 3. 刪除商品圖片資料庫紀錄
    $sql = "
    DELETE FROM PRODUCT_IMAGE
    WHERE product_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $product_id,
        $store_id
    ]);

    $deleted_images = $stmt->rowCount();

    // 4. 軟刪除商品
    $sql = "
    UPDATE PRODUCT
    SET
        status = 'deleted',
        updated_at = NOW()
    WHERE product_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $product_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("Failed to delete product");
    }

    // 5. 完成交易
    $pdo->commit();

    // 6. 刪除實體圖片
    $deleted_physical_images = 0;

    foreach ($images as $image) {

        $image_url = $image["image_url"];

        // 只允許刪除 uploads 目錄內的圖片
        if (
            !is_string($image_url) ||
            strpos($image_url, "/uploads/") !== 0
        ) {
            continue;
        }

        $image_path =
            dirname(__DIR__, 3)
            . $image_url;

        if (
            file_exists($image_path) &&
            is_file($image_path)
        ) {
            if (unlink($image_path)) {
                $deleted_physical_images++;
            }
        }
    }

    // 7. 回傳
    echo json_encode([
        "message" => "Product deleted successfully",
        "store_id" => $store_id,
        "product_id" => $product_id,
        "product_name" => $product["product_name"],
        "status" => "deleted",
        "deleted_cart_items" => $deleted_cart_items,
        "deactivated_specs" => $deactivated_specs,
        "deleted_image_records" => $deleted_images,
        "deleted_physical_images" => $deleted_physical_images
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