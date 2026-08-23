<?php

// Customer 建立退款申請

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";
require_once "../../../helpers/upload_image.php";

session_start();

// 檢查 Customer Session
if (
    !isset($_SESSION["customer_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = (int)$_SESSION["customer_id"];

// 檢查 Customer ID
if ($customer_id <= 0) {
    echo json_encode([
        "error" => "Invalid customer ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Customer 是否存在
$sql = "
SELECT
    customer_id
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

$order_id = $_POST["order_id"];
$refund_reason = trim($_POST["refund_reason"]);
$refund_description = trim($_POST["refund_description"]);

// 檢查 Order ID
if (
    !is_numeric($order_id) ||
    floor((float)$order_id) != (float)$order_id ||
    (int)$order_id <= 0
) {
    echo json_encode([
        "error" => "Invalid order ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = (int)$order_id;

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

// 取得訂單資料
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.customer_id,
    o.store_id,
    o.delivery_status,
    o.total_amount,
    o.estimated_arrival_date
FROM ORDERS o
WHERE o.order_id = ?
AND o.customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id,
    $customer_id
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// 訂單不存在
if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$order["store_id"];

// 檢查 Store Setting
$sql = "
SELECT
    refund_enable,
    refund_days_limit,
    store_mode
FROM STORE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store_setting = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果沒有 Store Setting
if (!$store_setting) {
    echo json_encode([
        "error" => "Store setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不能申請退款
if ($store_setting["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查退款功能是否開啟
if ((int)$store_setting["refund_enable"] !== 1) {
    echo json_encode([
        "error" => "Refund service is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得退款期限
$refund_days_limit = (int)$store_setting["refund_days_limit"];

// 防止錯誤設定
if ($refund_days_limit < 0) {
    echo json_encode([
        "error" => "Invalid refund days limit"
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

// 確認有到貨日期
if (empty($order["estimated_arrival_date"])) {
    echo json_encode([
        "error" => "Delivery date is unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 計算退款期限
// 目前先使用 estimated_arrival_date作為實際到貨日期
$refund_deadline = date(
    "Y-m-d",
    strtotime(
        $order["estimated_arrival_date"] .
        " +" .
        $refund_days_limit .
        " days"
    )
);

// 檢查是否超過退款期限
if (date("Y-m-d") > $refund_deadline) {
    echo json_encode([
        "error" => "Refund period has expired",
        "refund_deadline" => $refund_deadline
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查是否已經申請退款
$sql = "
SELECT
    refund_id,
    refund_status
FROM REFUND
WHERE order_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $order_id,
    $store_id
]);
$refund = $stmt->fetch(PDO::FETCH_ASSOC);

if ($refund) {
    echo json_encode([
        "error" => "Refund already requested",
        "refund_id" => (int)$refund["refund_id"],
        "refund_status" => $refund["refund_status"]
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查退款圖片
$image_url = null;

if (
    isset($_FILES["refund_image"]) &&
    $_FILES["refund_image"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    // 上傳錯誤
    if (
        $_FILES["refund_image"]["error"] !==
        UPLOAD_ERR_OK
    ) {
        echo json_encode([
            "error" => "Refund image upload failed"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $image = $_FILES["refund_image"];

    // 上傳圖片
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

// 建立退款申請
try {
    $sql = "
    INSERT INTO REFUND
    (
        order_id,
        store_id,
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
        'pending',
        NOW()
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $order_id,
        $store_id,
        $refund_reason,
        $refund_description,
        $image_url
    ]);
    $refund_id = (int)$pdo->lastInsertId();

} catch (PDOException $e) {
    echo json_encode([
        "error" => "Failed to create refund request"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "message" => "Refund request submitted successfully",

    "refund" => [
        "refund_id" => $refund_id,
        "order_number" => $order["order_number"],
        "store_id" => $store_id,
        "refund_reason" => $refund_reason,
        "refund_description" => $refund_description,
        "refund_image_url" => $image_url,
        "refund_status" => "pending",
        "requested_at" => date("Y-m-d H:i:s"),
        "refund_deadline" => $refund_deadline
    ]

], JSON_UNESCAPED_UNICODE);

?>