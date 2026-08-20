<?php

// Customer 取得指定 Store 的訂單列表

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

$stmt->execute([
    $customer_id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Store ID
if (!isset($_GET["store_id"])) {
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $_GET["store_id"];

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

// 取得篩選條件
$payment_status = $_GET["payment_status"] ?? "all";
$payment_confirm_status = $_GET["payment_confirm_status"] ?? "all";
$delivery_status = $_GET["delivery_status"] ?? "all";
$refund_status = $_GET["refund_status"] ?? "all";

// Payment Status
$allowed_payment_status = [
    "all",
    "pending",
    "processing",
    "paid",
    "failed"
];

if (!in_array($payment_status, $allowed_payment_status, true)) {
    echo json_encode([
        "error" => "Invalid payment status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Payment Confirm Status
$allowed_payment_confirm_status = [
    "all",
    "waiting",
    "confirmed",
    "rejected"
];

if (
    !in_array(
        $payment_confirm_status,
        $allowed_payment_confirm_status,
        true
    )
) {
    echo json_encode([
        "error" => "Invalid payment confirm status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Delivery Status
$allowed_delivery_status = [
    "all",
    "pending",
    "shipping",
    "completed"
];

if (!in_array($delivery_status, $allowed_delivery_status, true)) {
    echo json_encode([
        "error" => "Invalid delivery status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Refund Status
$allowed_refund_status = [
    "all",
    "pending",
    "approved",
    "rejected"
];

if (!in_array($refund_status, $allowed_refund_status, true)) {
    echo json_encode([
        "error" => "Invalid refund status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status AS store_status,
    ss.store_mode
FROM STORE s

INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
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

// 商店帳號停用
if ($store["store_status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式
if ($store["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 建立基本條件
$where = "
WHERE o.customer_id = ?
AND o.store_id = ?
";

$params = [
    $customer_id,
    $store_id
];

// Payment Status 篩選
if ($payment_status !== "all") {

    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p
            WHERE p.order_id = o.order_id
            AND p.store_id = o.store_id
            AND p.payment_status = ?
        )
    ";

    $params[] = $payment_status;
}

// Payment Confirm Status 篩選
if ($payment_confirm_status !== "all") {

    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p
            WHERE p.order_id = o.order_id
            AND p.store_id = o.store_id
            AND p.payment_confirm_status = ?
        )
    ";

    $params[] = $payment_confirm_status;
}

// Delivery Status 篩選
if ($delivery_status !== "all") {

    $where .= "
        AND o.delivery_status = ?
    ";

    $params[] = $delivery_status;
}

// Refund Status 篩選
if ($refund_status !== "all") {

    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r
            WHERE r.order_id = o.order_id
            AND r.store_id = o.store_id
            AND r.refund_status = ?
        )
    ";

    $params[] = $refund_status;
}

// 取得訂單
$sql = "
SELECT
    o.order_id,
    o.store_id,
    s.store_name,
    o.order_date,

    o.receiver_name,
    o.receiver_phone,
    o.receiver_address,

    o.product_amount,
    o.shipping_fee,
    o.total_amount,

    o.delivery_method,
    o.delivery_status,

    o.estimated_ship_date,
    o.estimated_arrival_date,

    o.created_at,
    o.updated_at

FROM ORDERS o

INNER JOIN STORE s
    ON o.store_id = s.store_id

$where

ORDER BY o.order_date DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 沒有訂單
if (!$orders) {

    echo json_encode([
        "message" => "No orders found",

        "customer_id" =>
            $customer_id,

        "store_id" =>
            $store_id,

        "filters" => [

            "payment_status" =>
                $payment_status,

            "payment_confirm_status" =>
                $payment_confirm_status,

            "delivery_status" =>
                $delivery_status,

            "refund_status" =>
                $refund_status
        ],

        "orders" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$result = [];

foreach ($orders as $order) {

    $order_id = (int)$order["order_id"];

    // Payment
    $sql = "
    SELECT
        payment_id,
        payment_method,
        amount,
        payment_status,
        payment_confirm_status,
        payment_note,
        payment_proof_image,
        paid_at,
        confirmed_at,
        created_at,
        updated_at

    FROM PAYMENT

    WHERE order_id = ?
    AND store_id = ?

    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $store_id
    ]);

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Refund
    $sql = "
    SELECT
        refund_id,
        refund_reason,
        refund_description,
        refund_image_url,
        refund_status,
        admin_reply,
        requested_at,
        processed_at

    FROM REFUND

    WHERE order_id = ?
    AND store_id = ?

    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $store_id
    ]);

    $refund = $stmt->fetch(PDO::FETCH_ASSOC);

    // Order Item
    $sql = "
    SELECT
        order_item_id,
        product_id,
        spec_id,
        product_name,
        spec_name,
        quantity,
        price

    FROM ORDER_ITEM

    WHERE order_id = ?
    AND store_id = ?

    ORDER BY order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $store_id
    ]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 商品資料型別轉換
    foreach ($items as &$item) {

        $item["order_item_id"] =
            (int)$item["order_item_id"];

        $item["product_id"] =
            (int)$item["product_id"];

        if ($item["spec_id"] !== null) {

            $item["spec_id"] =
                (int)$item["spec_id"];
        }

        $item["quantity"] =
            (int)$item["quantity"];

        $item["price"] =
            (float)$item["price"];

        $item["subtotal"] =
            $item["quantity"] *
            $item["price"];
    }

    unset($item);

    // Payment 資料型別轉換
    if ($payment) {

        $payment["payment_id"] =
            (int)$payment["payment_id"];

        $payment["amount"] =
            (float)$payment["amount"];
    }

    // Refund 資料型別轉換
    if ($refund) {

        $refund["refund_id"] =
            (int)$refund["refund_id"];
    }

    // 建立訂單結果
    $result[] = [

        "order_id" =>
            $order_id,

        "store_id" =>
            (int)$order["store_id"],

        "store_name" =>
            $order["store_name"],

        "order_date" =>
            $order["order_date"],

        "receiver" => [

            "name" =>
                $order["receiver_name"],

            "phone" =>
                $order["receiver_phone"],

            "address" =>
                $order["receiver_address"]
        ],

        "amount" => [

            "product_amount" =>
                (float)$order["product_amount"],

            "shipping_fee" =>
                (float)$order["shipping_fee"],

            "total_amount" =>
                (float)$order["total_amount"]
        ],

        "payment" =>
            $payment ?: null,

        "delivery" => [

            "delivery_method" =>
                $order["delivery_method"],

            "delivery_status" =>
                $order["delivery_status"],

            "estimated_ship_date" =>
                $order["estimated_ship_date"],

            "estimated_arrival_date" =>
                $order["estimated_arrival_date"]
        ],

        "refund" =>
            $refund ?: null,

        "items" =>
            $items,

        "created_at" =>
            $order["created_at"],

        "updated_at" =>
            $order["updated_at"]
    ];
}

// 回傳
echo json_encode([

    "message" =>
        "Orders retrieved successfully",

    "customer_id" =>
        $customer_id,

    "store_id" =>
        $store_id,

    "filters" => [

        "payment_status" =>
            $payment_status,

        "payment_confirm_status" =>
            $payment_confirm_status,

        "delivery_status" =>
            $delivery_status,

        "refund_status" =>
            $refund_status
    ],

    "orders" =>
        $result

], JSON_UNESCAPED_UNICODE);

?>