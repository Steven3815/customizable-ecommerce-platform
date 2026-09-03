<?php

// Store 取得輪播圖片管理資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/cors.php";
require_once "../../../../middleware/store_auth.php";


try {
    // 取得首頁輪播區塊是否啟用
    $sql = "
        SELECT intro_section_enable
        FROM WEBSITE_SETTING
        WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    $website_setting = $stmt->fetch(PDO::FETCH_ASSOC);

    $intro_section_enable =
        $website_setting
            ? (bool)$website_setting["intro_section_enable"]
            : false;

    // 取得目前 Store 的輪播圖片
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

    // 回傳
    echo json_encode([
        "message" => "Slider images retrieved successfully",
        "intro_section_enable" => $intro_section_enable,
        "store_id" => $store_id,
        "count" => count($slider_images),
        "max_count" => 5,
        "slider_images" => $slider_images
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>