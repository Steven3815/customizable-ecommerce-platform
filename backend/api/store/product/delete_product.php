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

// 檢查 product_id
$product_id = $_POST["product_id"] ?? null;

if ($product_id === null) {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    !is_numeric($product_id) ||
    floor($product_id) != $product_id
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

// 檢查商品是否已經刪除
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
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 開始交易
$pdo->beginTransaction();

try {
    // 移除購物車中的商品

    // 商品雖然是軟刪除，但客戶購物車不能繼續保留已刪除商品。
    $sql = "
    DELETE FROM CART_ITEM
    WHERE product_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);

    $deleted_cart_items = $stmt->rowCount();
    $sql = "
    UPDATE PRODUCT_SPEC
    SET
        status = 'inactive',
        updated_at = NOW()
    WHERE product_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    // 刪除商品圖片資料庫紀錄
    // PRODUCT 本身不刪除，但 PRODUCT_IMAGE 可以刪除。
    $sql = "
    DELETE FROM PRODUCT_IMAGE
    WHERE product_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);

    $deleted_images = $stmt->rowCount();

    // 軟刪除商品
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

    // 確認真的有更新

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Failed to delete product"
        );
    }

    // 完成交易
    $pdo->commit();

    // 刪除實體圖片
    $deleted_physical_images = 0;

    foreach ($images as $image) {

        $image_url = $image["image_url"];

        // 防止不正常的路徑

        if (
            !is_string($image_url) ||
            strpos($image_url, "/uploads/") !== 0
        ) {
            continue;
        }
        /*例如：
         /uploads/products/image_xxx.jpg 對應
         專案根目錄/uploads/products/image_xxx.jpg
         */
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

    // 回傳
    echo json_encode([
        "message" => "Product deleted successfully",
        "product_id" => $product_id,
        "product_name" => $product["product_name"],
        "store_id" => $store_id,
        "status" => "deleted",
        "deleted_cart_items" => $deleted_cart_items,
        "deleted_image_records" => $deleted_images,
        "deleted_physical_images" => $deleted_physical_images
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // 發生錯誤 → Rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>