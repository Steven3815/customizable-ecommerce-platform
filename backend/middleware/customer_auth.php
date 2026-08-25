<?php

session_start();

require_once __DIR__ . "/../config/database.php";

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

?>