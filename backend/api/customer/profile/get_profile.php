<?php

// Customer 取得會員資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 查詢會員資料
$sql = "
SELECT
    customer_id,
    name,
    email,
    phone,
    address,
    preferred_payment,
    preferred_delivery,
    created_at,
    updated_at
FROM CUSTOMER
WHERE customer_id = ?
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to retrieve customer profile"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 找不到會員
if (!$customer) {
    http_response_code(404);
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理資料型態
$customer["customer_id"] = (int)$customer["customer_id"];

// Payment 對應
$payment_map = [
    1 => "credit_card",
    2 => "atm",
    3 => "post_office",
    4 => "cash_on_delivery",
    5 => "in_store"
];

// Delivery 對應
$delivery_map = [
    1 => "home_delivery",
    2 => "convenience_store",
    3 => "store_pickup"
];

$preferred_payment = $customer["preferred_payment"];
$preferred_delivery = $customer["preferred_delivery"];
// 如果資料庫存的是 ID，轉成 method
$preferred_payment_method = null;

if (
    is_numeric($preferred_payment) &&
    isset($payment_map[(int)$preferred_payment])
) {
    $preferred_payment_method = $payment_map[(int)$preferred_payment];
} elseif (
    is_string($preferred_payment) &&
    in_array(
        $preferred_payment,
        $payment_map,
        true
    )
) {
    $preferred_payment_method = $preferred_payment;
}

// 如果資料庫存的是 ID，轉成 method
$preferred_delivery_method = null;

if (
    is_numeric($preferred_delivery) &&
    isset($delivery_map[(int)$preferred_delivery])
) {
    $preferred_delivery_method =
        $delivery_map[(int)$preferred_delivery];
} elseif (
    is_string($preferred_delivery) &&
    in_array(
        $preferred_delivery,
        $delivery_map,
        true
    )
) {
    $preferred_delivery_method =
        $preferred_delivery;
}

// 回傳
echo json_encode([
    "message" => "Customer profile retrieved successfully",
    "customer" => [
        "customer_id" => $customer["customer_id"],
        "name" => $customer["name"],
        "email" => $customer["email"],
        "phone" => $customer["phone"],
        "address" => $customer["address"],
        "preferred_payment" => $preferred_payment_method,
        "preferred_delivery" => $preferred_delivery_method,
        "created_at" => $customer["created_at"],
        "updated_at" => $customer["updated_at"]
    ]
], JSON_UNESCAPED_UNICODE);

?>