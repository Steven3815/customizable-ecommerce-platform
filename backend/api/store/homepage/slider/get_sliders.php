<?php

// Store 取得輪播圖片管理資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/database.php";

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

try {

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

    $stmt->execute([
        $store_id
    ]);

    $slider_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            "Slider images retrieved successfully",

        "store_id" =>
            $store_id,

        "count" =>
            count($slider_images),

        "max_count" =>
            5,

        "slider_images" =>
            $slider_images
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>