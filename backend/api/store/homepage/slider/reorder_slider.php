<?php

// Store 重新排列首頁輪播圖片

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
    !is_array($data["image_ids"])
) {
    echo json_encode([
        "error" => "Image IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$image_ids = $data["image_ids"];

// 至少一張圖片
if (count($image_ids) < 1) {
    echo json_encode([
        "error" => "At least one image is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 最多 5 張
if (count($image_ids) > 5) {
    echo json_encode([
        "error" => "Maximum 5 images are allowed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證 Image ID
$validated_image_ids = [];

foreach ($image_ids as $image_id) {

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

    // 防止重複 Image
    if (
        in_array(
            $image_id,
            $validated_image_ids,
            true
        )
    ) {
        echo json_encode([
            "error" => "Duplicate image ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $validated_image_ids[] = $image_id;
}

try {

    $pdo->beginTransaction();

    // 取得目前 Store 所有 active 輪播圖片
    $sql = "
        SELECT
            image_id
        FROM SLIDER_IMAGE
        WHERE store_id = ?
        AND status = 'active'
        ORDER BY sort_order ASC, image_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id
    ]);

    $existing_image_ids = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $existing_image_ids[] =
            (int)$row["image_id"];
    }

    // 確認數量完全一致
    if (
        count($validated_image_ids)
        !== count($existing_image_ids)
    ) {
        throw new Exception(
            "Image list is incomplete"
        );
    }

    // 建立目前 Store Image ID 對照表
    $existing_lookup = array_flip(
        $existing_image_ids
    );

    // 確認所有圖片都屬於目前 Store
    foreach ($validated_image_ids as $image_id) {

        if (!isset($existing_lookup[$image_id])) {

            throw new Exception(
                "Slider image not found"
            );
        }
    }

    // 暫時提高 sort_order
    // 避免重新排序時產生重複排序值
    $temporary_offset = 1000000;

    $sql = "
        UPDATE SLIDER_IMAGE
        SET
            sort_order = sort_order + ?
        WHERE store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $temporary_offset,
        $store_id
    ]);

    // 按照 image_ids 順序重新設定 sort_order
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

    foreach (
        $validated_image_ids
        as $index => $image_id
    ) {

        $sort_order = $index + 1;

        $stmt->execute([
            $sort_order,
            $image_id,
            $store_id
        ]);
    }

    $pdo->commit();

    // 取得新的排序結果
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

    $slider_images =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理資料
    foreach (
        $slider_images
        as &$image
    ) {

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

    echo json_encode([
        "message" =>
            "Slider images reordered successfully",

        "slider_images" =>
            $slider_images
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>