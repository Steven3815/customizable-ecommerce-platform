<?php

// Store 刪除首頁輪播圖片

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

// 取得 Image ID
$image_id = $_POST["image_id"] ?? null;

if (
    $image_id === null ||
    $image_id === ""
) {
    http_response_code(400);

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
    http_response_code(400);

    echo json_encode([
        "error" => "Invalid image ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_id = (int)$image_id;

// 確認 Slider Image 屬於目前 Store
$sql = "
SELECT
    image_id,
    store_id,
    image_url,
    title,
    sort_order,
    status
FROM SLIDER_IMAGE
WHERE image_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $image_id,
    $store_id
]);

$slider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slider) {
    http_response_code(404);

    echo json_encode([
        "error" => "Slider image not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// deleted Slider 禁止操作
if ($slider["status"] === "deleted") {
    http_response_code(409);

    echo json_encode([
        "error" => "Deleted slider image cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 沒有圖片
if (
    $slider["image_url"] === null ||
    $slider["image_url"] === ""
) {
    http_response_code(400);

    echo json_encode([
        "error" => "Slider image does not exist"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查圖片路徑
$image_url = $slider["image_url"];

if (
    !is_string($image_url) ||
    strpos($image_url, "/uploads/") !== 0
) {
    http_response_code(400);

    echo json_encode([
        "error" => "Invalid image path"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 開始交易
$pdo->beginTransaction();

try {

    // Soft Delete Slider Image
    $sql = "
    UPDATE SLIDER_IMAGE
    SET
        status = 'deleted',
        sort_order = NULL,
        updated_at = NOW()
    WHERE image_id = ?
    AND store_id = ?
    AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $image_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Failed to delete slider image"
        );
    }

    // 取得剩餘圖片
    $sql = "
    SELECT
        image_id
    FROM SLIDER_IMAGE
    WHERE store_id = ?
    AND status = 'active'
    ORDER BY sort_order ASC, image_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $remaining_image_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 重新整理 sort_order
    $sql = "
    UPDATE SLIDER_IMAGE
    SET
        sort_order = ?,
        updated_at = NOW()
    WHERE image_id = ?
    AND store_id = ?
    AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    foreach (
        $remaining_image_ids
        as $index => $remaining_image_id
    ) {

        $stmt->execute([
            $index + 1,
            $remaining_image_id,
            $store_id
        ]);
    }

    // 完成交易
    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status_code = $e->getCode();

    if (
        $status_code < 400 ||
        $status_code > 599
    ) {
        $status_code = 500;
    }

    http_response_code($status_code);

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$deleted_physical_image = deleteImage($image_url);

// 取得剩餘輪播圖片
$sql = "
SELECT
    image_id,
    store_id,
    image_url,
    title,
    sort_order,
    status,
    created_at,
    updated_at
FROM SLIDER_IMAGE
WHERE store_id = ?
AND status = 'active'
ORDER BY sort_order ASC, image_id ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$slider_images =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理資料
foreach ($slider_images as &$image) {

    $image["image_id"] =
        (int)$image["image_id"];

    $image["store_id"] =
        (int)$image["store_id"];

    $image["sort_order"] =
        $image["sort_order"] !== null
            ? (int)$image["sort_order"]
            : null;
}

unset($image);

// 回傳
echo json_encode([
    "message" =>
        "Slider image deleted successfully",

    "store_id" =>
        $store_id,

    "image_id" =>
        $image_id,

    "image_url" =>
        $image_url,

    "deleted_physical_image" =>
        $deleted_physical_image,

    "slider_images" =>
        $slider_images

], JSON_UNESCAPED_UNICODE);

?>