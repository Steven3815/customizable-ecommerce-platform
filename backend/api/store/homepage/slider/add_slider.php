<?php

// Store 新增首頁輪播圖片

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

// 檢查圖片
if (
    !isset($_FILES["image"]) ||
    !is_array($_FILES["image"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Image is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得標題
$title = trim($_POST["title"] ?? "");

// 空字串轉成 NULL
if ($title === "") {
    $title = null;
}

// 有標題時最多 200 字
if (
    $title !== null &&
    mb_strlen($title) > 200
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Title is too long"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_url = null;

try {

    $pdo->beginTransaction();

    // 確認目前 Store 的 active 輪播圖片數量
    $sql = "
        SELECT COUNT(*)
        FROM SLIDER_IMAGE
        WHERE store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $image_count = (int)$stmt->fetchColumn();

    // 最多 5 張
    if ($image_count >= 5) {
        throw new Exception("Maximum 5 slider images are allowed", 400);
    }

    // 取得下一個 sort_order
    $sql = "
        SELECT
            COALESCE(
                MAX(sort_order),
                0
            ) + 1
        FROM SLIDER_IMAGE
        WHERE store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $sort_order = (int)$stmt->fetchColumn();

    // 確保排序為 1 ~ 5
    if (
        $sort_order < 1 ||
        $sort_order > 5
    ) {
        throw new Exception("Invalid slider image sort order", 400);
    }

    // 上傳圖片
    $image_url = uploadImage(
        $_FILES["image"],
        "sliders",
        1920,
        600
    );

    // 新增 Slider Image
    $sql = "
        INSERT INTO SLIDER_IMAGE
        (
            store_id,
            image_url,
            title,
            sort_order,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'active'
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $image_url,
        $title,
        $sort_order
    ]);
    $image_id = (int)$pdo->lastInsertId();

    $pdo->commit();

    // 回傳新增資料
    echo json_encode([
        "message" => "Slider image added successfully",

        "slider_image" => [
            "image_id" => $image_id,
            "store_id" => $store_id,
            "image_url" => $image_url,
            "title" => $title,
            "sort_order" => $sort_order,
            "status" => "active"
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 如果圖片已經成功上傳但資料庫寫入失敗，刪除圖片
    if ($image_url !== null) {
        deleteImage($image_url);
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