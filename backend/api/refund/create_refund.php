<?php

// 建立退款
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";
require_once "../../helpers/upload_image.php";

session_start();

// 檢查 Customer Session
if (!isset($_SESSION["customer_id"])) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = (int)$_SESSION["customer_id"];

// 檢查必要欄位
if (
    !isset($_POST["order_id"]) ||
    !isset($_POST["refund_reason"]) ||
    !isset($_POST["refund_description"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$order_id = (int)$_POST["order_id"];

$refund_reason = trim($_POST["refund_reason"]);
$refund_description = trim($_POST["refund_description"]);

// 檢查退款原因
if ($refund_reason === "") {
    echo json_encode([
        "error" => "Refund reason is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查退款說明
if ($refund_description === "") {
    echo json_encode([
        "error" => "Refund description is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查退款圖片
$image_url = null;

if (
    isset($_FILES["refund_image"]) &&
    $_FILES["refund_image"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES["refund_image"]["error"] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "error" => "Refund image upload failed"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $image = $_FILES["refund_image"];

    // 檢查圖片類型
    $allowed_types = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    // 檢查 MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime_type = finfo_file(
        $finfo,
        $image["tmp_name"]
    );

    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types, true)) {
        echo json_encode([
            "error" => "Invalid image type"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 檢查圖片大小
    if ($image["size"] > 5 * 1024 * 1024) {
        echo json_encode([
            "error" => "Image size must be less than 5MB"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 上傳退款圖片
    try {
        $image_url = uploadImage(
            $image,
            "refunds"
        );
    } catch (Exception $e) {
        echo json_encode([
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 確認訂單屬於會員
$sql = "
SELECT
    order_id,
    delivery_status,
    total_amount,
    estimated_arrival_date
FROM ORDERS
WHERE order_id = ?
AND customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $customer_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 確認訂單已送達
if ($order["delivery_status"] !== "completed") {
    echo json_encode([
        "error" => "Order has not been delivered yet"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 計算退款期限
// 目前先使用 estimated_arrival_date 當作實際到貨日期
$refund_deadline = date(
    "Y-m-d",
    strtotime(
        $order["estimated_arrival_date"] . " +7 days"
    )
);

// 檢查是否超過退款期限
if (date("Y-m-d") > $refund_deadline) {
    echo json_encode([
        "error" => "Refund period has expired"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查是否已經申請退款
$sql = "
SELECT refund_id
FROM REFUND
WHERE order_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

if ($refund) {
    echo json_encode([
        "error" => "Refund already requested"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 建立退款申請
$sql = "
INSERT INTO REFUND
(
    order_id,
    refund_reason,
    refund_description,
    refund_image_url,
    refund_status,
    requested_at
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    NOW()
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $refund_reason,
    $refund_description,
    $image_url,
    "pending"
]);

// 取得退款 ID
$refund_id = (int)$pdo->lastInsertId();

// 回傳
echo json_encode([
    "message" => "Refund request submitted successfully",
    "refund_id" => $refund_id,
    "order_id" => $order_id,
    "refund_status" => "pending",
    "refund_image_url" => $image_url
], JSON_UNESCAPED_UNICODE);

?>