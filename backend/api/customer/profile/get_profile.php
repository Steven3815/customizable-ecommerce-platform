<?php

// Customer 取得會員資料

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

// 檢查 customer_id
if ($customer_id <= 0) {
    echo json_encode([
        "error" => "Invalid customer ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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
    echo json_encode([
        "error" => "Failed to retrieve customer profile"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 找不到會員
if (!$customer) {

    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理資料型態
$customer["customer_id"] = (int)$customer["customer_id"];

// 回傳會員資料
echo json_encode([
    "message" => "Customer profile retrieved successfully",
    "customer" => $customer
], JSON_UNESCAPED_UNICODE);

?>