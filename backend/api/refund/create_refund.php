<?php

// 建立退款
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

if (
    !isset($_POST["customer_id"]) ||
    !isset($_POST["order_id"]) ||
    !isset($_POST["refund_reason"]) ||
    !isset($_POST["refund_description"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$customer_id = $_POST["customer_id"];
$order_id = $_POST["order_id"];
$refund_reason = $_POST["refund_reason"];
$refund_description = $_POST["refund_description"];

// 接收退款圖片

if (!isset($_FILES["refund_image"])) {
    echo json_encode([
        "error" => "Refund image is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$image = $_FILES["refund_image"];

if ($image["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "error" => "Image upload failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查圖片類型
$allowed_types = [
    "image/jpeg",
    "image/png",
    "image/webp"
];

if (!in_array($image["type"], $allowed_types)) {
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
// 目前先使用「預計到貨時間」當作實際到貨時間
$refund_deadline = date(
    "Y-m-d",
    strtotime($order["estimated_arrival_date"] . " +7 days")
);

// 檢查是否超過退款期限
if (date("Y-m-d") > $refund_deadline) {
    echo json_encode([
        "error" => "Refund period has expired"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查是否已經申請過退款
$sql = "
SELECT refund_id
FROM REFUND
WHERE order_id = ?
";


$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id
]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

if ($refund) {
    echo json_encode([
        "error" => "Refund already requested"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 建立退款圖片資料夾
$upload_dir = "../../uploads/refunds/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 取得圖片副檔名
$extension = pathinfo(
    $image["name"],
    PATHINFO_EXTENSION
);

// 建立唯一圖片檔名
$file_name = uniqid(
    "refund_",
    true
) . "." . $extension;

// 建立圖片完整路徑
$file_path = $upload_dir . $file_name;

// 儲存圖片
if (!move_uploaded_file(
    $image["tmp_name"],
    $file_path
)) {
    echo json_encode([
        "error" => "Failed to save refund image"
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
    $file_path,
    "pending"
]);

echo json_encode([
    "message" => "Refund request submitted successfully",
    "refund_id" => $pdo->lastInsertId(),
    "order_id" => $order_id,
    "refund_status" => "pending",
    "refund_image_url" => $file_path
], JSON_UNESCAPED_UNICODE);

?>
