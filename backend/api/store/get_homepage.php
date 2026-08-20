<?php

// Store 首頁 Dashboard

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

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

// 取得 Store
$sql = "
SELECT
    store_id,
    store_name,
    store_url,
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
    store_status,
    store_mode,
    stock_alert_enable,
    stock_alert_threshold,
    spec_stock_alert_threshold
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

// 取得會員數
// 以曾經向此 Store 下過訂單的不同 Customer 計算
$sql = "
SELECT COUNT(DISTINCT customer_id)
FROM ORDERS
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$member_count =
    (int)$stmt->fetchColumn();

// 今日訂單數
$sql = "
SELECT COUNT(*)
FROM ORDERS
WHERE store_id = ?
AND created_at >= CURDATE()
AND created_at < DATE_ADD(
    CURDATE(),
    INTERVAL 1 DAY
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$today_orders =
    (int)$stmt->fetchColumn();

// 今日營收
// 只計算已付款訂單
$sql = "
SELECT COALESCE(SUM(p.amount), 0)
FROM PAYMENT p
JOIN ORDERS o
    ON p.order_id = o.order_id
    AND p.store_id = o.store_id
WHERE p.store_id = ?
AND p.payment_status = 'paid'
AND p.created_at >= CURDATE()
AND p.created_at < DATE_ADD(
    CURDATE(),
    INTERVAL 1 DAY
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$today_revenue =
    (float)$stmt->fetchColumn();

// 本月營收
$sql = "
SELECT COALESCE(SUM(p.amount), 0)
FROM PAYMENT p
JOIN ORDERS o
    ON p.order_id = o.order_id
    AND p.store_id = o.store_id
WHERE p.store_id = ?
AND p.payment_status = 'paid'
AND p.created_at >= DATE_FORMAT(
    CURDATE(),
    '%Y-%m-01'
)
AND p.created_at < DATE_ADD(
    DATE_FORMAT(
        CURDATE(),
        '%Y-%m-01'
    ),
    INTERVAL 1 MONTH
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$monthly_revenue =
    (float)$stmt->fetchColumn();

// 待確認收款
$sql = "
SELECT COUNT(*)
FROM PAYMENT
WHERE store_id = ?
AND payment_confirm_status = 'waiting'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$pending_payment_confirm =
    (int)$stmt->fetchColumn();

// 待出貨
$sql = "
SELECT COUNT(*)
FROM ORDERS
WHERE store_id = ?
AND delivery_status = 'pending'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$pending_shipment =
    (int)$stmt->fetchColumn();

// 待處理退款
$sql = "
SELECT COUNT(*)
FROM REFUND
WHERE store_id = ?
AND refund_status = 'pending'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$pending_refund =
    (int)$stmt->fetchColumn();

// 庫存預警設定
$stock_alert_enable =
    (bool)$store_setting["stock_alert_enable"];

$stock_alert_threshold =
    $store_setting["stock_alert_threshold"] !== null
        ? (int)$store_setting["stock_alert_threshold"]
        : 0;

$spec_stock_alert_threshold =
    $store_setting["spec_stock_alert_threshold"] !== null
        ? (int)$store_setting["spec_stock_alert_threshold"]
        : 0;

$stock_alert_count = 0;

// 庫存預警
if ($stock_alert_enable) {

    // 無規格商品庫存預警
    $sql = "
    SELECT COUNT(*)
    FROM PRODUCT
    WHERE store_id = ?
    AND status = 'active'
    AND has_spec = FALSE
    AND stock > 0
    AND stock <= ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id,
        $stock_alert_threshold
    ]);

    $product_stock_alert_count =
        (int)$stmt->fetchColumn();

    // 有規格商品庫存預警
    // 一個商品只計算一次
    $sql = "
    SELECT COUNT(DISTINCT ps.product_id)
    FROM PRODUCT_SPEC ps
    JOIN PRODUCT p
        ON ps.store_id = p.store_id
        AND ps.product_id = p.product_id
    WHERE ps.store_id = ?
    AND p.status = 'active'
    AND p.has_spec = TRUE
    AND ps.status = 'active'
    AND ps.stock > 0
    AND ps.stock <= ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id,
        $spec_stock_alert_threshold
    ]);

    $spec_stock_alert_count =
        (int)$stmt->fetchColumn();

    // 一般商品 + 有規格商品
    $stock_alert_count =
        $product_stock_alert_count
        + $spec_stock_alert_count;
}

// 庫存不足
// 一個商品只計算一次

$product_out_of_stock_count = 0;

$spec_out_of_stock_count = 0;

// 無規格商品庫存不足
$sql = "
SELECT COUNT(*)
FROM PRODUCT
WHERE store_id = ?
AND status = 'active'
AND has_spec = FALSE
AND stock <= 0
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$product_out_of_stock_count =
    (int)$stmt->fetchColumn();

// 有規格商品庫存不足
// 只要任一規格庫存不足
// 該商品就只計算一次
$sql = "
SELECT COUNT(DISTINCT ps.product_id)
FROM PRODUCT_SPEC ps
JOIN PRODUCT p
    ON ps.store_id = p.store_id
    AND ps.product_id = p.product_id
WHERE ps.store_id = ?
AND p.status = 'active'
AND p.has_spec = TRUE
AND ps.status = 'active'
AND ps.stock <= 0
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$spec_out_of_stock_count =
    (int)$stmt->fetchColumn();

// 一般商品 + 有規格商品
$out_of_stock_count =
    $product_out_of_stock_count
    + $spec_out_of_stock_count;

// 最近訂單
// 最多顯示 10 筆
$sql = "
SELECT
    o.order_id,
    o.created_at,
    c.name AS customer_name,
    o.total_amount,
    o.delivery_status
FROM ORDERS o
JOIN CUSTOMER c
    ON o.customer_id = c.customer_id
WHERE o.store_id = ?
ORDER BY
    o.created_at DESC,
    o.order_id DESC
LIMIT 10
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$recent_orders =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理最近訂單資料
foreach ($recent_orders as &$order) {

    $order["order_id"] =
        (int)$order["order_id"];

    $order["total_amount"] =
        (float)$order["total_amount"];
}

unset($order);

// 回傳 Dashboard
echo json_encode([

    "store" => [

        "store_id" =>
            (int)$store["store_id"],

        "store_name" =>
            $store["store_name"],

        "store_url" =>
            $store["store_url"]
    ],

    "summary" => [

        "member_count" =>
            $member_count,

        "today_orders" =>
            $today_orders,

        "today_revenue" =>
            $today_revenue,

        "monthly_revenue" =>
            $monthly_revenue
    ],

    "recent_orders" =>
        $recent_orders,

    "notifications" => [

        "pending_payment_confirm" =>
            $pending_payment_confirm,

        "pending_shipment" =>
            $pending_shipment,

        "pending_refund" =>
            $pending_refund,

        "stock_alert" =>
            $stock_alert_count,

        "out_of_stock" =>
            $out_of_stock_count
    ]

], JSON_UNESCAPED_UNICODE);

?>