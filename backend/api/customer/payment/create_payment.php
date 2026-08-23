<?php

// Customer 建立 / 修改付款

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

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON
if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["order_id"]) ||
    !isset($data["store_id"]) ||
    !isset($data["payment_method"])
) {
    echo json_encode([
        "error" => "Order ID, store ID and payment method are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$order_id = $data["order_id"];
$store_id = $data["store_id"];

$payment_method = trim($data["payment_method"]);

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

// 檢查付款方式
$allowed_payment_methods = [
    "credit_card",
    "atm",
    "post_office",
    "cash_on_delivery",
    "in_store"
];

if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {
    echo json_encode([
        "error" => "Invalid payment method"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得訂單
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.customer_id,
    o.store_id,

    o.product_amount,
    o.shipping_fee,
    o.total_amount,

    o.receiver_name,
    o.receiver_phone,
    o.receiver_address,

    o.delivery_method,
    o.delivery_status,

    c.name AS customer_name,
    c.email AS customer_email,
    c.phone AS customer_phone,
    c.address AS customer_address

FROM ORDERS o

INNER JOIN CUSTOMER c
    ON o.customer_id = c.customer_id

WHERE o.order_id = ?
AND o.customer_id = ?
AND o.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $customer_id,
    $store_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// 訂單不存在
if (!$order) {
    echo json_encode([
        "error" => "Order not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查配送方式是否存在
if (
    $order["delivery_method"] === null ||
    trim($order["delivery_method"]) === ""
) {
    echo json_encode([
        "error" => "Order delivery method is not set"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$delivery_method = $order["delivery_method"];

// 檢查 Store 與 Store Setting
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status AS store_status,

    ss.store_status AS business_status,
    ss.store_mode

FROM STORE s

INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store setting not found"
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

// 商店暫停營業
if ($store["business_status"] !== "open") {
    echo json_encode([
        "error" => "Store is currently closed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不能付款
if ($store["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_name = $store["store_name"];

// 訂單只能在 pending 時進行付款設定
if ($order["delivery_status"] !== "pending") {
    echo json_encode([
        "error" => "Order cannot be paid after shipping"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否開放此付款方式
$sql = "
SELECT
    store_payment_id,
    payment_method,
    status

FROM STORE_PAYMENT_METHOD

WHERE store_id = ?
AND payment_method = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id,
    $payment_method
]);

$store_payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store_payment) {
    echo json_encode([
        "error" => "Payment method is not available for this store"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($store_payment["status"] !== "active") {
    echo json_encode([
        "error" => "Selected payment method is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否開放訂單中的配送方式
$sql = "
SELECT
    store_delivery_id,
    delivery_method,
    status

FROM STORE_DELIVERY_METHOD

WHERE store_id = ?
AND delivery_method = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id,
    $delivery_method
]);
$store_delivery = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store_delivery) {
    echo json_encode([
        "error" => "Order delivery method is no longer available"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($store_delivery["status"] !== "active") {
    echo json_encode([
        "error" => "Order delivery method is currently unavailable"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得目前 Payment
$sql = "
SELECT
    payment_id,
    order_id,
    store_id,
    payment_method,
    amount,
    payment_status,
    payment_confirm_status

FROM PAYMENT

WHERE order_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $order_id,
    $store_id
]);

$existing_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// 檢查目前 Payment 狀態
if ($existing_payment) {

    // 已經付款完成
    if (
        $existing_payment["payment_status"] === "paid" &&
        $existing_payment["payment_confirm_status"] === "confirmed"
    ) {
        echo json_encode([
            "error" => "Order has already been paid"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 已經送出轉帳，Store 等待確認
    if (
        $existing_payment["payment_status"] === "processing" &&
        $existing_payment["payment_confirm_status"] === "waiting"
    ) {
        echo json_encode([
            "error" => "Payment is already waiting for store confirmation"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 其他非 pending 狀態
    if (
        $existing_payment["payment_status"] !== "pending"
    ) {
        echo json_encode([
            "error" => "Payment cannot be modified in its current status"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // pending 必須搭配 waiting
    if (
        $existing_payment["payment_confirm_status"] !== "waiting"
    ) {
        echo json_encode([
            "error" => "Payment cannot be modified"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 建立 / 修改 Payment
try {

    $pdo->beginTransaction();

    // 沒有 Payment → 建立
    if (!$existing_payment) {

        $sql = "
        INSERT INTO PAYMENT
        (
            order_id,
            store_id,
            payment_method,
            amount,
            payment_status,
            payment_confirm_status,
            created_at,
            updated_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'pending',
            'waiting',
            NOW(),
            NOW()
        )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $order_id,
            $store_id,
            $payment_method,
            $order["total_amount"]
        ]);

        $payment_id = (int)$pdo->lastInsertId();

        $message = "Payment created successfully";

    } else {

        // 已有 Payment，而且是 pending → 修改原本 Payment
        $sql = "
        UPDATE PAYMENT
        SET
            payment_method = ?,
            amount = ?,
            updated_at = NOW()

        WHERE payment_id = ?
        AND order_id = ?
        AND store_id = ?
        AND payment_status = 'pending'
        AND payment_confirm_status = 'waiting'
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $payment_method,
            $order["total_amount"],
            $existing_payment["payment_id"],
            $order_id,
            $store_id
        ]);

        if ($stmt->rowCount() !== 1) {

            $pdo->rollBack();

            echo json_encode([
                "error" => "Payment could not be modified"
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $payment_id = (int)$existing_payment["payment_id"];

        $message = "Payment updated successfully";
    }

    $pdo->commit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => "Payment creation failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 決定下一步
$next_action = null;

switch ($payment_method) {

    case "credit_card":
        $next_action = "credit_card";
        break;

    case "atm":
        $next_action = "bank_transfer";
        break;

    case "post_office":
        $next_action = "post_office_transfer";
        break;

    case "cash_on_delivery":
        $next_action = "complete_order";
        break;

    case "in_store":
        $next_action = "complete_order";
        break;
}

// 回傳
echo json_encode([
    "message" => $message,
    "payment" => [
        "payment_id" => $payment_id,
        "order_number" => $order["order_number"],
        "store_id" => $store_id,
        "payment_method" => $payment_method,
        "amount" => (float)$order["total_amount"],
        "payment_status" => "pending",
        "payment_confirm_status" => "waiting",
        "next_action" => $next_action
    ],
    "order" => [
        "order_number" => $order["order_number"],
        "customer_id" => (int)$order["customer_id"],
        "store_id" => $store_id,
        "product_amount" => (float)$order["product_amount"],
        "shipping_fee" => (float)$order["shipping_fee"],
        "total_amount" => (float)$order["total_amount"],
        "receiver_name" => $order["receiver_name"],
        "receiver_email" => $order["customer_email"],
        "receiver_phone" => $order["receiver_phone"],
        "receiver_address" => $order["receiver_address"],
        "delivery_method" => $delivery_method
    ],
    "store" => [
        "store_id" => $store_id,
        "store_name" => $store_name
    ]
], JSON_UNESCAPED_UNICODE);

?>