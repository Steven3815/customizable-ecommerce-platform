<?php

// Store 新增首頁 Banner

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

// 取得 Banner 標題
$title = trim($_POST["title"] ?? "");

// 空字串轉成 NULL
if ($title === "") {
    $title = null;
}

// 有標題時最多 20 字
if ($title !== null && mb_strlen($title) > 20) {
    http_response_code(400);
    echo json_encode([
        "error" => "Title is too long"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得描述
$description = trim($_POST["description"] ?? "");

// 描述最多 80 字
if (mb_strlen($description) > 80) {
    http_response_code(400);
    echo json_encode([
        "error" => "Description is too long"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得預設圖片 ID
$default_banner_id = $_POST["default_banner_id"] ?? null;

// 是否有上傳圖片
$has_upload_image =
    isset($_FILES["image"]) &&
    is_array($_FILES["image"]) &&
    isset($_FILES["image"]["error"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE;

// 是否選擇預設圖片
$has_default_banner =
    $default_banner_id !== null &&
    $default_banner_id !== "";

// 圖片來源必須二選一
if (
    ($has_default_banner && $has_upload_image) ||
    (!$has_default_banner && !$has_upload_image)
) {
    http_response_code(400);
    echo json_encode([
        "error" =>
            "Please select either a default banner or upload an image"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_url = null;
$uploaded_image_url = null;

try {

    $pdo->beginTransaction();

    // 確認目前 Store 尚未建立 Banner
    $sql = "
        SELECT
            banner_id
        FROM PROMOTION_BANNER
        WHERE store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    if ($stmt->fetch()) {
        throw new Exception("Banner already exists", 409);
    }

    // 使用預設 Banner
    if ($has_default_banner) {

        // 驗證預設 Banner ID
        if (
            !is_numeric($default_banner_id) ||
            floor((float)$default_banner_id)
            != (float)$default_banner_id
        ) {
            throw new Exception("Invalid default banner ID", 400);
        }

        $default_banner_id = (int)$default_banner_id;

        if ($default_banner_id <= 0) {
            throw new Exception("Invalid default banner ID", 400);
        }

        // 取得預設圖片
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
            throw new Exception("Default banner not found", 404);
        }

        // 使用預設 Banner 的圖片
        $image_url = $default_banner["image_url"];
    }

    // 使用 Store 上傳圖片
    else {

        $uploaded_image_url = uploadImage(
            $_FILES["image"],
            "banners",
            1920,
            600
        );

        $image_url = $uploaded_image_url;

        $default_banner_id = null;
    }

    // 新增 Banner
    $sql = "
        INSERT INTO PROMOTION_BANNER
        (
            store_id,
            default_banner_id,
            image_url,
            title,
            description,
            sort_order,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            1,
            'active'
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $default_banner_id,
        $image_url,
        $title,
        $description
    ]);

    $banner_id = (int)$pdo->lastInsertId();

    $pdo->commit();

    // 回傳新增資料
    echo json_encode([
    "message" => "Banner added successfully",
    "banner" => [
        "banner_id" => $banner_id,
        "store_id" => $store_id,
        "default_banner_id" => $default_banner_id,
        "image_url" => $image_url,
        "title" => $title,
        "description" => $description,
        "sort_order" => 1,
        "status" => "active"
    ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 如果是上傳圖片但資料庫新增失敗,刪除剛上傳的圖片
    if ($uploaded_image_url !== null) {
        deleteImage($uploaded_image_url);
    }

    $status_code = $e->getCode();
    if ($status_code < 400 || $status_code > 599) {
        $status_code = 500;
    }
    http_response_code($status_code);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>