<?php

// Store 刪除首頁輪播圖片

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

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

// 檢查 image_ids
if (
    !isset($data["image_ids"]) ||
    !is_array($data["image_ids"]) ||
    count($data["image_ids"]) < 1
) {
    echo json_encode([
        "error" => "Image IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$validated_image_ids = [];

foreach ($data["image_ids"] as $image_id) {

    if (
        !is_numeric($image_id) ||
        floor((float)$image_id) != (float)$image_id
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

    if (in_array($image_id, $validated_image_ids, true)) {
        echo json_encode([
            "error" => "Duplicate image ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $validated_image_ids[] = $image_id;
}

$deleted_image_urls = [];

try {

    $pdo->beginTransaction();

    // 確認所有圖片都屬於目前 Store
    $sql = "
        SELECT
            image_id,
            image_url
        FROM SLIDER_IMAGE
        WHERE image_id = ?
        AND store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($validated_image_ids as $image_id) {

        $stmt->execute([
            $image_id,
            $store_id
        ]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$image) {
            throw new Exception("Slider image not found");
        }

        $deleted_image_urls[] = $image["image_url"];
    }

    // Soft Delete
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

    foreach ($validated_image_ids as $image_id) {

        $stmt->execute([
            $image_id,
            $store_id
        ]);
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

    foreach ($remaining_image_ids as $index => $image_id) {

        $stmt->execute([
            $index + 1,
            $image_id,
            $store_id
        ]);
    }

    $pdo->commit();

    // 刪除實體圖片
    foreach ($deleted_image_urls as $image_url) {
        deleteImage($image_url);
    }

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
    $stmt->execute([$store_id]);
    $slider_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理資料
    foreach ($slider_images as &$image) {
        $image["image_id"] = (int)$image["image_id"];
        $image["store_id"] = (int)$image["store_id"];
        $image["sort_order"] =
            $image["sort_order"] !== null
                ? (int)$image["sort_order"]
                : null;
    }

    unset($image);

    echo json_encode([
        "message" => "Slider images deleted successfully",
        "slider_images" => $slider_images
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