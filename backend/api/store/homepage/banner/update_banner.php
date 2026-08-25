<?php

// Store 更新首頁 Banner

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

// 檢查 banner_id
$banner_id = $_POST["banner_id"] ?? null;

if (
    $banner_id === null ||
    !is_numeric($banner_id) ||
    floor((float)$banner_id) != (float)$banner_id
) {
    echo json_encode([
        "error" => "Invalid banner ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$banner_id = (int)$banner_id;

if ($banner_id <= 0) {
    echo json_encode([
        "error" => "Invalid banner ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 至少修改圖片、標題或描述
if (
    !isset($_POST["default_banner_id"]) &&
    !isset($_FILES["image"]) &&
    !isset($_POST["title"]) &&
    !isset($_POST["description"])
) {
    echo json_encode([
        "error" => "Nothing to update"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$new_uploaded_image_url = null;
$old_image_url = null;

try {

    $pdo->beginTransaction();

    // 取得目前 Banner
    $sql = "
        SELECT
            banner_id,
            store_id,
            default_banner_id,
            image_url,
            title,
            description,
            sort_order,
            status
        FROM PROMOTION_BANNER
        WHERE banner_id = ?
        AND store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $banner_id,
        $store_id
    ]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$banner) {
        throw new Exception("Banner not found");
    }

    $old_image_url = $banner["image_url"];

    // 判斷圖片來源
    $has_default_banner =
        isset($_POST["default_banner_id"]) &&
        $_POST["default_banner_id"] !== "";

    $has_upload_image =
        isset($_FILES["image"]) &&
        is_array($_FILES["image"]) &&
        isset($_FILES["image"]["error"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE;

    // 圖片來源必須二選一
    if (
        $has_default_banner &&
        $has_upload_image
    ) {
        throw new Exception("Choose either default banner or uploaded image");
    }

    // 使用預設 Banner
    if ($has_default_banner) {

        $default_banner_id = $_POST["default_banner_id"];

        // 驗證 ID
        if (
            !is_numeric($default_banner_id) ||
            floor((float)$default_banner_id)
                != (float)$default_banner_id
        ) {
            throw new Exception("Invalid default banner ID");
        }

        $default_banner_id = (int)$default_banner_id;

        if ($default_banner_id <= 0) {
            throw new Exception("Invalid default banner ID");
        }

        // 確認預設 Banner 存在
        $sql = "
            SELECT
                default_banner_id,
                image_url
            FROM DEFAULT_BANNER
            WHERE default_banner_id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$default_banner_id]);
        $default_banner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$default_banner) {
            throw new Exception("Default banner not found");
        }

        $new_default_banner_id = $default_banner_id;
        $new_image_url = $default_banner["image_url"];
    }

    // 使用 Store 上傳圖片
    elseif ($has_upload_image) {

        $new_default_banner_id = null;

        $new_uploaded_image_url =
            uploadImage(
                $_FILES["image"],
                "banners",
                1920,
                600
            );

        $new_image_url = $new_uploaded_image_url;
    }

    // 沒有修改圖片
    else {

        $new_default_banner_id = $banner["default_banner_id"];
        $new_image_url = $banner["image_url"];
    }

    // 更新標題
    if (isset($_POST["title"])) {

        $title = trim($_POST["title"]);

        // 空字串轉成 NULL
        if ($title === "") {
            $title = null;
        }

        // 有標題時最多 20 字
        if (
            $title !== null &&
            mb_strlen($title) > 20
        ) {
            throw new Exception("Title is too long");
        }

    } else {

        $title = $banner["title"];
    }

    // 更新描述
    if (isset($_POST["description"])) {

        $description = trim($_POST["description"]);

        if (mb_strlen($description) > 80) {
            throw new Exception("Description is too long");
        }

    } else {

        $description = $banner["description"];
    }

    // 更新 Banner
    $sql = "
        UPDATE PROMOTION_BANNER
        SET
            default_banner_id = ?,
            image_url = ?,
            title = ?,
            description = ?,
            updated_at = NOW()
        WHERE banner_id = ?
        AND store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $new_default_banner_id,
        $new_image_url,
        $title,
        $description,
        $banner_id,
        $store_id
    ]);

    $pdo->commit();

    // 刪除舊的 Store 上傳圖片
    if (
        $banner["default_banner_id"] === null &&
        $old_image_url !== null &&
        $new_image_url !== $old_image_url
    ) {
        deleteImage($old_image_url);
    }

    // 回傳
    echo json_encode([
        "message" => "Banner updated successfully",
        "banner" => [
            "banner_id" => $banner_id,
            "store_id" => $store_id,
            "default_banner_id" => $new_default_banner_id !== null
                ? (int)$new_default_banner_id
                : null,
            "image_url" => $new_image_url,
            "title" => $title,
            "description" => $description,
            "sort_order" => $banner["sort_order"] !== null
                ? (int)$banner["sort_order"]
                : null,
            "status" => $banner["status"]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // DB 更新失敗 只有這次真的上傳的新圖片才刪除
    if ($new_uploaded_image_url !== null) {
        deleteImage($new_uploaded_image_url);
    }
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>