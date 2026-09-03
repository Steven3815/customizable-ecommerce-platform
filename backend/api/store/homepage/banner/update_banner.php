<?php

// Store 更新首頁 Banner

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

// 檢查 banner_id
$banner_id = $_POST["banner_id"] ?? null;

if (
    $banner_id === null ||
    !is_numeric($banner_id) ||
    floor((float)$banner_id) != (float)$banner_id
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid banner ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$banner_id = (int)$banner_id;

if ($banner_id <= 0) {
    http_response_code(400);

    echo json_encode([
        "error" => "Invalid banner ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得圖片來源
$image_source = $_POST["image_source"] ?? null;

// 圖片來源只能是 default 或 upload
if (
    $image_source !== "default" &&
    $image_source !== "upload"
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid image source"
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
    http_response_code(400);
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
        throw new Exception(
            "Banner not found",
            404
        );
    }

    $old_image_url = $banner["image_url"];

    // 使用預設 Banner
    if ($image_source === "default") {
        $default_banner_id =
            $_POST["default_banner_id"] ?? null;

        if (
            $default_banner_id === null ||
            $default_banner_id === ""
        ) {
            throw new Exception(
                "Default banner ID is required",
                400
            );
        }

        // 驗證 ID
        if (
            !is_numeric($default_banner_id) ||
            floor((float)$default_banner_id)
            != (float)$default_banner_id
        ) {
            throw new Exception(
                "Invalid default banner ID",
                400
            );
        }

        $default_banner_id = (int)$default_banner_id;

        if ($default_banner_id <= 0) {
            throw new Exception(
                "Invalid default banner ID",
                400
            );
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
            throw new Exception(
                "Default banner not found",
                404
            );
        }

        $new_default_banner_id = $default_banner_id;
        $new_image_url = $default_banner["image_url"];
    }

    // 使用 Store 上傳圖片
    elseif ($image_source === "upload") {
        $new_default_banner_id = null;

        // 有重新選擇圖片
        if (
            isset($_FILES["image"]) &&
            is_array($_FILES["image"]) &&
            isset($_FILES["image"]["error"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {
            $new_uploaded_image_url = uploadImage(
                $_FILES["image"],
                "banners"
            );

            $new_image_url = $new_uploaded_image_url;
        }

        // 沒有重新選擇圖片
        else {
            // 原本就是 Store 上傳的圖片
            if (
                $banner["default_banner_id"] === null &&
                $banner["image_url"] !== null
            ) {
                $new_image_url =
                    $banner["image_url"];
            }

            // 原本是預設 Banner
            // 現在要改成 Store 上傳
            else {
                throw new Exception(
                    "Image is required",
                    400
                );
            }
        }
    }

    // 更新標題
    if (isset($_POST["title"])) {
        $title = trim($_POST["title"]);

        if ($title === "") {
            $title = null;
        }

        if (
            $title !== null &&
            mb_strlen($title) > 20
        ) {
            throw new Exception("Title is too long", 400);
        }
    } else {
        $title = $banner["title"];
    }

    // 更新描述
    if (isset($_POST["description"])) {
        $description =
            trim($_POST["description"]);

        if (mb_strlen($description) > 80) {
            throw new Exception("Description is too long", 400);
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

    // 確認確實更新
    if ($stmt->rowCount() === 0) {
        throw new Exception("Banner update failed",500);
    }

    $pdo->commit();

    // 如果原本是 Store 上傳圖片，
    // 而且這次換成其他圖片，刪除舊圖片
    if (
        $banner["default_banner_id"] === null &&
        $old_image_url !== null &&
        $new_image_url !== $old_image_url
    ) {
        deleteImage($old_image_url);
    }

    echo json_encode([
        "message" => "Banner updated successfully",
        "banner" => [
            "banner_id" => $banner_id,
            "store_id" => $store_id,
            "default_banner_id" =>
                $new_default_banner_id !== null
                    ? (int)$new_default_banner_id
                    : null,
            "image_source" => $image_source,
            "image_url" => $new_image_url,
            "title" => $title,
            "description" => $description,
            "sort_order" =>
                $banner["sort_order"] !== null
                    ? (int)$banner["sort_order"]
                    : null,
            "status" => $banner["status"]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // DB 更新失敗時，
    // 刪除這次新上傳的圖片
    if ($new_uploaded_image_url !== null) {
        deleteImage($new_uploaded_image_url);
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

?>