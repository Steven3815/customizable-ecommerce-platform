<?php

// Customer 建立客服案件

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
    !isset($_POST["store_id"]) ||
    !isset($_POST["problem_type"]) ||
    !isset($_POST["description"])
) {
    echo json_encode([
        "error" => "Store ID, problem type and description are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $_POST["store_id"];
$problem_type = trim($_POST["problem_type"]);
$description = trim($_POST["description"]);

// 檢查 Store ID
if (
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$store_id;

// 檢查問題類型
$allowed_problem_types = [
    "product",
    "order",
    "payment",
    "delivery",
    "refund",
    "other"
];

if (!in_array($problem_type, $allowed_problem_types, true)) {
    echo json_encode([
        "error" => "Invalid problem type"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查問題描述
if ($description === "") {
    echo json_encode([
        "error" => "Description is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (mb_strlen($description) > 2000) {
    echo json_encode([
        "error" => "Description must be less than 2000 characters"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Order ID 可選
$order_id = null;

if (
    isset($_POST["order_id"]) &&
    $_POST["order_id"] !== ""
) {
    if (
        !is_numeric($_POST["order_id"]) ||
        floor((float)$_POST["order_id"]) != (float)$_POST["order_id"] ||
        (int)$_POST["order_id"] <= 0
    ) {
        echo json_encode([
            "error" => "Invalid order ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $order_id = (int)$_POST["order_id"];
}

// 檢查 Store
$sql = "
SELECT
    store_id,
    store_name,
    status
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否啟用
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Store Setting
$sql = "
SELECT
    store_mode,
    customer_service_enable
FROM STORE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store_setting) {
    echo json_encode([
        "error" => "Store setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不可使用客服
if ($store_setting["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查客服功能是否開啟
if ((int)$store_setting["customer_service_enable"] !== 1) {
    echo json_encode([
        "error" => "Customer service is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查是否已有 pending 客服案件
$sql = "
SELECT
    service_id
FROM CUSTOMER_SERVICE
WHERE customer_id = ?
AND store_id = ?
AND status = 'pending'
LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id,
    $store_id
]);

$existing_service = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_service) {
    echo json_encode([
        "error" => "You already have a pending customer service request"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_number = null;

// 如果有 Order ID，確認訂單屬於 Customer + Store
if ($order_id !== null) {
    $sql = "
    SELECT
        order_id,
        order_number,
        customer_id,
        store_id
    FROM ORDERS
    WHERE order_id = ?
    AND customer_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $customer_id,
        $store_id
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode([
            "error" => "Order not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
    $order_number = $order["order_number"];
}

// 檢查客服圖片
$image_url = null;

if (
    isset($_FILES["service_image"]) &&
    $_FILES["service_image"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES["service_image"]["error"] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "error" => "Service image upload failed"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $image = $_FILES["service_image"];

    $allowed_types = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

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

    if ($image["size"] > 5 * 1024 * 1024) {
        echo json_encode([
            "error" => "Image size must be less than 5MB"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {
        $image_url = uploadImage(
            $image,
            "customer_service"
        );
    } catch (Exception $e) {
        echo json_encode([
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 建立客服案件
try {
    $sql = "
    INSERT INTO CUSTOMER_SERVICE
    (
        customer_id,
        store_id,
        order_id,
        problem_type,
        description,
        image_url,
        status,
        created_at,
        updated_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        'pending',
        NOW(),
        NOW()
    )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $customer_id,
        $store_id,
        $order_id,
        $problem_type,
        $description,
        $image_url
    ]);

    $service_id = (int)$pdo->lastInsertId();

} catch (PDOException $e) {
    echo json_encode([
        "error" => "Failed to create customer service request"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "message" => "Customer service request created successfully",
    "service" => [
        "service_id" => $service_id,
        "customer_id" => $customer_id,
        "store_id" => $store_id,
        "order_number" => $order_number,
        "problem_type" => $problem_type,
        "description" => $description,
        "image_url" => $image_url,
        "status" => "pending"
    ]
], JSON_UNESCAPED_UNICODE);

?>