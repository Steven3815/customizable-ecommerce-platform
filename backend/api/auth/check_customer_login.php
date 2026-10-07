<?php

// Check Customer Login

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";
require_once "../../config/database.php";

session_start();

// 檢查 Customer Session
if (
    !isset($_SESSION["customer_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    http_response_code(401);

    echo json_encode([
        "logged_in" => false
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = (int)$_SESSION["customer_id"];

// 檢查 Customer ID
if ($customer_id <= 0) {
    http_response_code(401);

    echo json_encode([
        "logged_in" => false
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Customer 是否存在
$sql = "
SELECT
    customer_id,
    name,
    email,
    phone,
    address
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Customer 不存在
if (!$customer) {
    http_response_code(401);

    echo json_encode([
        "logged_in" => false
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 登入狀態確認成功
echo json_encode([
    "logged_in" => true,

    "customer" => [
        "customer_id" => (int)$customer["customer_id"],
        "name" => $customer["name"],
        "email" => $customer["email"],
        "phone" => $customer["phone"],
        "address" => $customer["address"]
    ],

    "session" => [
        "customer_id" => $_SESSION["customer_id"],
        "role" => $_SESSION["role"]
    ]

], JSON_UNESCAPED_UNICODE);

?>