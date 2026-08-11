<?php

// Customer 登入

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

session_start();

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON 格式
if (!is_array($data)) {

    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["email"]) ||
    !isset($data["password"])
) {

    echo json_encode([
        "error" => "Email and password are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得資料
$email = trim($data["email"]);
$password = $data["password"];

// 檢查 Email
if ($email === "") {

    echo json_encode([
        "error" => "Email is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 統一 Email 大小寫
$email = strtolower($email);

// 檢查 Email 格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "error" => "Invalid email format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查密碼
if ($password === "") {

    echo json_encode([
        "error" => "Password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 查詢會員
$sql = "
SELECT
    customer_id,
    name,
    email,
    password,
    phone,
    address
FROM CUSTOMER
WHERE email = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到會員
if (!$customer) {

    echo json_encode([
        "error" => "Invalid email or password"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證密碼
if (!password_verify(
    $password,
    $customer["password"]
)) {

    echo json_encode([
        "error" => "Invalid email or password"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 登入成功
// 防止 Session Fixation
session_regenerate_id(true);

// 建立 Customer Session
$_SESSION["customer_id"] = (int)$customer["customer_id"];
$_SESSION["role"] = "customer";

// 回傳
echo json_encode([
    "message" => "Login successful",

    "customer" => [
        "customer_id" => (int)$customer["customer_id"],
        "name" => $customer["name"],
        "email" => $customer["email"],
        "phone" => $customer["phone"],
        "address" => $customer["address"]
    ],

    "session" => [
        "session_id" => session_id(),
        "customer_id" => $_SESSION["customer_id"],
        "role" => $_SESSION["role"]
    ]

], JSON_UNESCAPED_UNICODE);

?>