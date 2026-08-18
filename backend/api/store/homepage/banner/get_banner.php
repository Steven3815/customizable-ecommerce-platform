<?php

// Store 取得首頁 Banner 管理資料

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

    // 取得目前 Store 的 Banner
    $sql = "
        SELECT
            pb.banner_id,
            pb.store_id,
            pb.default_banner_id,
            pb.image_url AS upload_image_url,
            pb.title,
            pb.description,
            pb.sort_order,
            pb.status,
            pb.created_at,
            pb.updated_at,
            db.image_url AS default_image_url,
            db.name AS default_banner_name
        FROM PROMOTION_BANNER pb
        LEFT JOIN DEFAULT_BANNER db
            ON pb.default_banner_id = db.default_banner_id
        WHERE pb.store_id = ?
        AND pb.status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id
    ]);

    $banner = $stmt->fetch(PDO::FETCH_ASSOC);

    // 整理目前 Banner
    if ($banner) {

        $banner["banner_id"] =
            (int)$banner["banner_id"];

        $banner["store_id"] =
            (int)$banner["store_id"];

        $banner["default_banner_id"] =
            $banner["default_banner_id"] !== null
                ? (int)$banner["default_banner_id"]
                : null;

        $banner["sort_order"] =
            $banner["sort_order"] !== null
                ? (int)$banner["sort_order"]
                : null;

        // 判斷圖片來源
        if (
            $banner["default_banner_id"] !== null
        ) {

            $banner["image_source"] =
                "default";

            $banner["image_url"] =
                $banner["default_image_url"];

        } else {

            $banner["image_source"] =
                "upload";

            $banner["image_url"] =
                $banner["upload_image_url"];
        }

        unset(
            $banner["upload_image_url"],
            $banner["default_image_url"]
        );

    } else {

        $banner = null;
    }

    // 取得所有預設 Banner
    $sql = "
        SELECT
            default_banner_id,
            image_url,
            name,
            created_at
        FROM DEFAULT_BANNER
        ORDER BY default_banner_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute();

    $default_banners =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理預設 Banner
    foreach (
        $default_banners
        as &$default_banner
    ) {

        $default_banner["default_banner_id"] =
            (int)$default_banner["default_banner_id"];
    }

    unset($default_banner);

    // 回傳
    echo json_encode([
        "message" =>
            "Banner management data retrieved successfully",

        "store_id" =>
            $store_id,

        "banner" =>
            $banner,

        "default_banners" =>
            $default_banners

    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "error" =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>