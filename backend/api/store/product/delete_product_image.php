<?php

// Store 刪除商品圖片

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

// 檢查 image_id
$image_id = $_POST["image_id"] ?? null;

if ($image_id === null || $image_id === "") {
    echo json_encode([
        "error" => "Image ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    !is_numeric($image_id) ||
    floor($image_id) != $image_id
) {
    echo json_encode([
        "error" => "Invalid image ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_id = (int)$image_id;

if ($image_id <= 0) {
    echo json_encode([
        "error" => "Invalid image ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 確認圖片屬於目前 Store 的商品
$sql = "
SELECT
    pi.image_id,
    pi.product_id,
    pi.image_url,
    p.product_name,
    p.store_id,
    p.status
FROM PRODUCT_IMAGE pi
INNER JOIN PRODUCT p
    ON pi.product_id = p.product_id
WHERE pi.image_id = ?
AND p.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $image_id,
    $store_id
]);

$image = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$image) {
    echo json_encode([
        "error" => "Image not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// deleted 商品禁止操作
if ($image["status"] === "deleted") {
    echo json_encode([
        "error" => "Deleted product image cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查商品至少要保留一張圖片
$sql = "
SELECT COUNT(*) AS image_count
FROM PRODUCT_IMAGE
WHERE product_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $image["product_id"]
]);

$image_count = (int)$stmt->fetchColumn();

if ($image_count <= 1) {
    echo json_encode([
        "error" => "Product must have at least one image"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查圖片路徑
$image_url = $image["image_url"];

if (
    !is_string($image_url) ||
    strpos($image_url, "/uploads/") !== 0
) {
    echo json_encode([
        "error" => "Invalid image path"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 建立實體圖片路徑
$image_path =
    dirname(__DIR__, 3) . $image_url;

// 開始交易
$pdo->beginTransaction();

try {

    // 刪除圖片資料庫紀錄
    $sql = "
    DELETE FROM PRODUCT_IMAGE
    WHERE image_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $image_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Failed to delete image record"
        );
    }

    // 完成交易
    $pdo->commit();

    // 刪除實體圖片
    $deleted_physical_image = false;

    if (
        file_exists($image_path) &&
        is_file($image_path)
    ) {
        $deleted_physical_image =
            unlink($image_path);
    }

    // 回傳
    echo json_encode([
        "message" => "Product image deleted successfully",
        "store_id" => $store_id,
        "product_id" => (int)$image["product_id"],
        "product_name" => $image["product_name"],
        "image_id" => $image_id,
        "image_url" => $image_url,
        "deleted_physical_image" =>
            $deleted_physical_image
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