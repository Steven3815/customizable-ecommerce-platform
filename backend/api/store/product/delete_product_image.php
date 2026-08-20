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

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Image ID
$image_id = $_POST["image_id"] ?? null;

if (
    $image_id === null ||
    $image_id === ""
) {
    echo json_encode([
        "error" => "Image ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Image ID
if (
    !is_numeric($image_id) ||
    floor((float)$image_id) != (float)$image_id ||
    (int)$image_id <= 0
) {
    echo json_encode([
        "error" => "Invalid image ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_id = (int)$image_id;

// 確認圖片屬於目前 Store 的商品
$sql = "
SELECT
    pi.image_id,
    pi.product_id,
    pi.store_id,
    pi.image_url,
    pi.sort_order,
    p.product_name,
    p.status
FROM PRODUCT_IMAGE pi
INNER JOIN PRODUCT p
    ON pi.product_id = p.product_id
    AND pi.store_id = p.store_id
WHERE pi.image_id = ?
AND pi.store_id = ?
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

// 檢查商品至少保留一張圖片
$sql = "
SELECT
    COUNT(*)
FROM PRODUCT_IMAGE
WHERE product_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $image["product_id"],
    $store_id
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

// 儲存商品資訊
$product_id =
    (int)$image["product_id"];

$product_name =
    $image["product_name"];

// 開始交易
$pdo->beginTransaction();

try {

    // 1. 刪除圖片資料庫紀錄
    $sql = "
    DELETE FROM PRODUCT_IMAGE
    WHERE image_id = ?
    AND product_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $image_id,
        $product_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Failed to delete image record"
        );
    }

    // 2. 取得刪除後剩餘圖片
    $sql = "
    SELECT
        image_id
    FROM PRODUCT_IMAGE
    WHERE product_id = ?
    AND store_id = ?
    ORDER BY sort_order ASC, image_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $product_id,
        $store_id
    ]);

    $remaining_images =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. 重新整理圖片排序
    // 從 1 開始
    $sort_order = 1;

    foreach ($remaining_images as $remaining_image) {

        $remaining_image_id =
            (int)$remaining_image["image_id"];

        $sql = "
        UPDATE PRODUCT_IMAGE
        SET
            sort_order = ?
        WHERE image_id = ?
        AND product_id = ?
        AND store_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $sort_order,
            $remaining_image_id,
            $product_id,
            $store_id
        ]);

        $sort_order++;
    }

    // 4. 完成交易
    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 5. 刪除實體圖片
$deleted_physical_image = false;

if (
    file_exists($image_path) &&
    is_file($image_path)
) {
    $deleted_physical_image =
        unlink($image_path);
}

// 6. 重新取得剩餘圖片
$sql = "
SELECT
    image_id,
    image_url,
    sort_order
FROM PRODUCT_IMAGE
WHERE product_id = ?
AND store_id = ?
ORDER BY sort_order ASC, image_id ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $product_id,
    $store_id
]);

$remaining_images =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理資料型別
foreach ($remaining_images as &$remaining_image) {

    $remaining_image["image_id"] =
        (int)$remaining_image["image_id"];

    $remaining_image["sort_order"] =
        (int)$remaining_image["sort_order"];
}

unset($remaining_image);

// 7. 回傳
echo json_encode([
    "message" =>
        "Product image deleted successfully",

    "store_id" =>
        $store_id,

    "product_id" =>
        $product_id,

    "product_name" =>
        $product_name,

    "image_id" =>
        $image_id,

    "image_url" =>
        $image_url,

    "deleted_physical_image" =>
        $deleted_physical_image,

    "remaining_image_count" =>
        count($remaining_images),

    "images" =>
        $remaining_images

], JSON_UNESCAPED_UNICODE);

?>