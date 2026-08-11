<?php

// Customer 取得訂單列表
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

// 篩選條件
$status = $_GET["status"] ?? "all";

// 檢查會員
$sql = "
SELECT customer_id
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

// 訂單篩選條件
$where = "WHERE o.customer_id = ?";
$params = [$customer_id];

// Payment 狀態
if ($status === "all") {
    // 不增加條件
} elseif ($status === "pending_payment") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p2
            WHERE p2.order_id = o.order_id
            AND p2.payment_status = 'pending'
        )
    ";
} elseif ($status === "paid") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p2
            WHERE p2.order_id = o.order_id
            AND p2.payment_status = 'paid'
        )
    ";
} elseif ($status === "failed") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p2
            WHERE p2.order_id = o.order_id
            AND p2.payment_status = 'failed'
        )
    ";
}

// Payment 確認狀態
elseif ($status === "payment_waiting") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p2
            WHERE p2.order_id = o.order_id
            AND p2.payment_confirm_status = 'waiting'
        )
    ";
} elseif ($status === "payment_confirmed") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p2
            WHERE p2.order_id = o.order_id
            AND p2.payment_confirm_status = 'confirmed'
        )
    ";
} elseif ($status === "payment_rejected") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM PAYMENT p2
            WHERE p2.order_id = o.order_id
            AND p2.payment_confirm_status = 'rejected'
        )
    ";
}

// Delivery 狀態
elseif ($status === "delivery_pending") {
    $where .= "
        AND o.delivery_status = 'pending'
    ";
} elseif ($status === "shipping") {
    $where .= "
        AND o.delivery_status = 'shipping'
    ";
} elseif ($status === "completed") {
    $where .= "
        AND o.delivery_status = 'completed'
    ";
}

// Refund 狀態
elseif ($status === "refund_pending") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r2
            WHERE r2.order_id = o.order_id
            AND r2.refund_status = 'pending'
        )
    ";
} elseif ($status === "refund_approved") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r2
            WHERE r2.order_id = o.order_id
            AND r2.refund_status = 'approved'
        )
    ";
} elseif ($status === "refund_rejected") {
    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r2
            WHERE r2.order_id = o.order_id
            AND r2.refund_status = 'rejected'
        )
    ";
} else {
    echo json_encode([
        "error" => "Invalid status"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得訂單
$sql = "
SELECT
    o.order_id,
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
$where
ORDER BY o.order_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$orders) {
    echo json_encode([
        "message" => "No orders found",
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
        payment_status,
        payment_confirm_status,
        payment_note,
        payment_proof_image,
        paid_at,
        confirmed_at
    FROM PAYMENT
    WHERE order_id = ?
    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);

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
    ORDER BY requested_at DESC
    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);

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
    ORDER BY order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理商品資料
    foreach ($items as &$item) {
        $item["order_item_id"] = (int)$item["order_item_id"];
        $item["product_id"] = (int)$item["product_id"];

        if ($item["spec_id"] !== null) {
            $item["spec_id"] = (int)$item["spec_id"];
        }

        $item["quantity"] = (int)$item["quantity"];
        $item["price"] = (float)$item["price"];
        $item["subtotal"] =
            $item["quantity"] * $item["price"];
    }

    unset($item);

    // 整理 Payment
    if ($payment) {
        $payment["payment_id"] = (int)$payment["payment_id"];
    }

    // 整理 Refund
    if ($refund) {
        $refund["refund_id"] = (int)$refund["refund_id"];
    }

    $result[] = [
        "order_id" => $order_id,
        "order_date" => $order["order_date"],

        "receiver" => [
            "name" => $order["receiver_name"],
            "phone" => $order["receiver_phone"],
            "address" => $order["receiver_address"]
        ],

        "amount" => [
            "product_amount" => (float)$order["product_amount"],
            "shipping_fee" => (float)$order["shipping_fee"],
            "total_amount" => (float)$order["total_amount"]
        ],

        "payment" => $payment ?: null,

        "delivery" => [
            "delivery_method" => $order["delivery_method"],
            "delivery_status" => $order["delivery_status"],
            "estimated_ship_date" => $order["estimated_ship_date"],
            "estimated_arrival_date" => $order["estimated_arrival_date"]
        ],

        "refund" => $refund ?: null,
        "items" => $items,

        "created_at" => $order["created_at"],
        "updated_at" => $order["updated_at"]
    ];
}

echo json_encode([
    "orders" => $result
], JSON_UNESCAPED_UNICODE);

?>