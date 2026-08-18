<?php

// Store 更新首頁輪播圖片

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/database.php";
require_once "../../../../helpers/upload_image.php";

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

// 檢查 image_id
$image_id = $_POST["image_id"] ?? null;

if (
    $image_id === null ||
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

// 至少要修改標題或圖片
if (
    !isset($_POST["title"]) &&
    !isset($_FILES["image"])
) {
    echo json_encode([
        "error" => "Nothing to update"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$new_image_url = null;
$old_image_url = null;

try {

    $pdo->beginTransaction();

    // 取得目前 Slider Image
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
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $image_id,
        $store_id
    ]);

    $slider = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slider) {
        throw new Exception(
            "Slider image not found"
        );
    }

    $old_image_url = $slider["image_url"];

    // 更新標題
    if (isset($_POST["title"])) {

        $title = trim($_POST["title"]);

        if (mb_strlen($title) > 200) {
            throw new Exception(
                "Title is too long"
            );
        }

    } else {

        // 沒有傳 title
        // 保留原本標題
        $title = $slider["title"];
    }

    // 更新圖片
    if (isset($_FILES["image"])) {

        $new_image_url = uploadImage(
            $_FILES["image"],
            "sliders"
        );

        $image_url = $new_image_url;

    } else {

        // 沒有新圖片
        // 保留原本圖片
        $image_url = $old_image_url;
    }

    // 更新 Slider Image
    $sql = "
        UPDATE SLIDER_IMAGE
        SET
            image_url = ?,
            title = ?,
            updated_at = NOW()
        WHERE image_id = ?
        AND store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $image_url,
        $title,
        $image_id,
        $store_id
    ]);

    // Commit
    $pdo->commit();

    // DB 成功後再刪除舊圖片
    if (
        $new_image_url !== null &&
        $old_image_url !== null &&
        $new_image_url !== $old_image_url
    ) {
        deleteImage($old_image_url);
    }

    // 回傳更新後資料
    echo json_encode([
        "message" =>
            "Slider image updated successfully",

        "slider_image" => [
            "image_id" =>
                $image_id,

            "store_id" =>
                $store_id,

            "image_url" =>
                $image_url,

            "title" =>
                $title,

            "sort_order" =>
                $slider["sort_order"] !== null
                    ? (int)$slider["sort_order"]
                    : null,

            "status" =>
                $slider["status"]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // DB 更新失敗
    // 刪除剛上傳的新圖片
    if ($new_image_url !== null) {
        deleteImage($new_image_url);
    }

    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>