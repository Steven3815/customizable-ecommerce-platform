<?php

// Store 刪除首頁橫幅圖片

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

// 取得 Banner ID
$banner_id = $_POST["banner_id"] ?? null;

if (
    $banner_id === null ||
    $banner_id === ""
) {
    http_response_code(400);

    echo json_encode([
        "error" => "Banner ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Banner ID
if (
    !is_numeric($banner_id) ||
    floor((float)$banner_id) != (float)$banner_id ||
    (int)$banner_id <= 0
) {
    http_response_code(400);

    echo json_encode([
        "error" => "Invalid banner ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$banner_id = (int)$banner_id;

// 確認 Banner 屬於目前 Store
$sql = "
SELECT
    banner_id,
    store_id,
    default_banner_id,
    image_url,
    title,
    description,
    status
FROM PROMOTION_BANNER
WHERE banner_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $banner_id,
    $store_id
]);

$banner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$banner) {
    http_response_code(404);

    echo json_encode([
        "error" => "Banner not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// deleted Banner 禁止操作
if ($banner["status"] === "deleted") {
    http_response_code(409);

    echo json_encode([
        "error" => "Deleted banner cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 沒有圖片
if (
    $banner["image_url"] === null ||
    $banner["image_url"] === ""
) {
    http_response_code(400);

    echo json_encode([
        "error" => "Banner image does not exist"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 預設 Banner 不允許刪除圖片
if ($banner["default_banner_id"] !== null) {
    http_response_code(400);

    echo json_encode([
        "error" => "Default banner image cannot be deleted"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查圖片路徑
$image_url = $banner["image_url"];

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

    // 清除 Banner 圖片
    $sql = "
    UPDATE PROMOTION_BANNER
    SET
        image_url = NULL,
        updated_at = NOW()
    WHERE banner_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $banner_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Failed to delete banner image"
        );
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

// 使用 helper 刪除實體圖片
$deleted_physical_image = deleteImage($image_url);

// 回傳
echo json_encode([
    "message" => "Banner image deleted successfully",
    "store_id" => $store_id,
    "banner_id" => $banner_id,
    "image_url" => $image_url,
    "deleted_physical_image" => $deleted_physical_image
], JSON_UNESCAPED_UNICODE);

?>