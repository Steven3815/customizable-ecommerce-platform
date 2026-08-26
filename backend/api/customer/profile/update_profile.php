<?php

// Customer 修改會員資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Email / Password 不允許修改
if (
    array_key_exists("email", $data) ||
    array_key_exists("password", $data)
) {
    http_response_code(409);
    echo json_encode([
        "error" => "Email and password cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 允許修改的欄位
$allowed_fields = [
    "name",
    "phone",
    "address",
    "preferred_payment",
    "preferred_delivery"
];

// 確認至少有一個欄位需要修改
$has_update = false;

foreach ($allowed_fields as $field) {

    if (array_key_exists($field, $data)) {
        $has_update = true;
        break;
    }
}

if (!$has_update) {
    http_response_code(400);
    echo json_encode([
        "error" => "No fields to update"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 查詢目前會員資料
$sql = "
SELECT
    customer_id,
    name,
    email,
    phone,
    address,
    preferred_payment,
    preferred_delivery
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 使用原本資料作為預設值
$name = $customer["name"];
$phone = $customer["phone"];
$address = $customer["address"];
$preferred_payment = $customer["preferred_payment"];
$preferred_delivery = $customer["preferred_delivery"];


// 修改姓名
if (array_key_exists("name", $data)) {

    $name = trim($data["name"]);

    if ($name === "") {

        http_response_code(400);
        echo json_encode([
            "error" => "Name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 修改電話
if (array_key_exists("phone", $data)) {
    $phone = trim($data["phone"]);
}

// 修改地址
if (array_key_exists("address", $data)) {
    $address = trim($data["address"]);
}

// 修改預設付款方式
if (array_key_exists("preferred_payment", $data)) {
    $preferred_payment = trim($data["preferred_payment"]);
    $allowed_payment_methods = [
        "credit_card",
        "atm",
        "post_office",
        "cash_on_delivery",
        "in_store"
    ];

    if (
        !in_array(
            $preferred_payment,
            $allowed_payment_methods,
            true
        )
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid preferred payment method"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 修改預設配送方式
if (array_key_exists("preferred_delivery", $data)) {

    $preferred_delivery =
        trim($data["preferred_delivery"]);

    $allowed_delivery_methods = [
        "home_delivery",
        "convenience_store",
        "store_pickup"
    ];

    if (
        !in_array(
            $preferred_delivery,
            $allowed_delivery_methods,
            true
        )
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid preferred delivery method"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 更新 Customer
$sql = "
UPDATE CUSTOMER

SET
    name = ?,
    phone = ?,
    address = ?,
    preferred_payment = ?,
    preferred_delivery = ?,
    updated_at = NOW()

WHERE customer_id = ?
";

try {

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $phone,
        $address,
        $preferred_payment,
        $preferred_delivery,
        $customer_id
    ]);

    $pdo->commit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "error" => "Profile update failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "message" => "Profile updated successfully",
    "customer" => [
        "customer_id" => $customer_id,
        "name" => $name,
        "email" => $customer["email"],
        "phone" => $phone,
        "address" => $address,
        "preferred_payment" => $preferred_payment,
        "preferred_delivery" => $preferred_delivery
    ]
], JSON_UNESCAPED_UNICODE);

?>