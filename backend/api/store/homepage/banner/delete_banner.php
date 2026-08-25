<?php

// Store 刪除首頁 Banner

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";
require_once "../../../../helpers/upload_image.php";

$old_image_url = null;

try {

    $pdo->beginTransaction();

    // 取得目前 Store 的 Banner
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
        WHERE store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);

    // 沒有 Banner
    if (!$banner) {
        throw new Exception("Banner not found");
    }

    // 判斷是否為 Store 上傳圖片
    $default_banner_id = $banner["default_banner_id"];
    $old_image_url = $banner["image_url"];

    // Soft Delete
    $sql = "
        UPDATE PROMOTION_BANNER
        SET
            status = 'deleted',
            sort_order = NULL,
            updated_at = NOW()
        WHERE banner_id = ?
        AND store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $banner["banner_id"],
        $store_id
    ]);

    $pdo->commit();

    // 如果是 Store 上傳圖片才刪除實體圖片
    if (
        $default_banner_id === null &&
        $old_image_url !== null
    ) {
        deleteImage($old_image_url);
    }

    // 回傳
    echo json_encode([
        "message" => "Banner deleted successfully",
        "banner_id" => (int)$banner["banner_id"],
        "store_id" => $store_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    // Rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>