<?php

// Customer 取得客服案件詳細資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

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

// 檢查 Store ID
if (!isset($_GET["store_id"])) {
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Service ID
if (!isset($_GET["service_id"])) {
    echo json_encode([
        "error" => "Service ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $_GET["store_id"];
$service_id = $_GET["service_id"];

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

// 檢查 Service ID
if (
    !is_numeric($service_id) ||
    floor((float)$service_id) != (float)$service_id ||
    (int)$service_id <= 0
) {
    echo json_encode([
        "error" => "Invalid service ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$service_id = (int)$service_id;

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

// 客服功能未開啟
if ((int)$store_setting["customer_service_enable"] !== 1) {
    echo json_encode([
        "error" => "Customer service is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得客服案件
$sql = "
SELECT
    cs.service_id,
    cs.customer_id,
    cs.store_id,
    cs.order_id,

    cs.problem_type,
    cs.description,
    cs.image_url,

    cs.status,
    cs.admin_reply,

    cs.created_at,
    cs.updated_at,

    s.store_name,

    o.order_number,
    o.total_amount,
    o.delivery_method,
    o.delivery_status,
    o.estimated_ship_date,
    o.estimated_arrival_date

FROM CUSTOMER_SERVICE cs

JOIN STORE s
    ON cs.store_id = s.store_id

LEFT JOIN ORDERS o
    ON cs.order_id = o.order_id
    AND cs.store_id = o.store_id

WHERE cs.service_id = ?
AND cs.customer_id = ?
AND cs.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $service_id,
    $customer_id,
    $store_id
]);

$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    echo json_encode([
        "error" => "Service not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理資料
$result = [
    "service_id" => (int)$service["service_id"],

    "store" => [
        "store_id" => (int)$service["store_id"],
        "store_name" => $service["store_name"]
    ],

    "order" => $service["order_id"] !== null
        ? [
            "order_number" => $service["order_number"],
            "total_amount" =>
                $service["total_amount"] !== null
                    ? (float)$service["total_amount"]
                    : null,

            "delivery_method" =>
                $service["delivery_method"],

            "delivery_status" =>
                $service["delivery_status"],

            "estimated_ship_date" =>
                $service["estimated_ship_date"],

            "estimated_arrival_date" =>
                $service["estimated_arrival_date"]
        ]
        : null,

    "problem_type" =>
        $service["problem_type"],

    "description" =>
        $service["description"],

    "image_url" =>
        $service["image_url"],

    "status" =>
        $service["status"],

    "admin_reply" =>
        $service["admin_reply"],

    "created_at" =>
        $service["created_at"],

    "updated_at" =>
        $service["updated_at"]
];

// 回傳
echo json_encode([
    "service" => $result
], JSON_UNESCAPED_UNICODE);

?>