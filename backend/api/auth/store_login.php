<?php

// Store 登入

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";
require_once "../../config/database.php";

session_start();

// 取得資料
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查必要欄位
if (
    !isset($data["email"]) ||
    !isset($data["password"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$email = trim($data["email"]);
$password = $data["password"];

// 檢查 Email 格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Please enter a valid email address"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Email 統一小寫
$email = strtolower($email);

// 檢查密碼是否為空
if ($password === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 查詢商家
$sql = "
SELECT
    store_id,
    store_name,
    store_url,
    email,
    password,
    owner_name,
    phone,
    status
FROM STORE
WHERE email = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到商家
if (!$store) {
    http_response_code(401);
    echo json_encode([
        "error" => "Invalid email or password"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查商家狀態
if ($store["status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store account is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證密碼
if (!password_verify($password, $store["password"])) {
    http_response_code(401);
    echo json_encode([
        "error" => "Invalid email or password"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 防止 Session Fixation
session_regenerate_id(true);

// 建立 Store Session
$_SESSION["store_id"] = (int)$store["store_id"];
$_SESSION["role"] = "store";

// 登入成功
echo json_encode([
    "message" => "Store login successful",

    "store" => [
        "store_id" => (int)$store["store_id"],
        "store_name" => $store["store_name"],
        "store_url" => $store["store_url"],
        "email" => $store["email"],
        "owner_name" => $store["owner_name"],
        "phone" => $store["phone"]
    ],

], JSON_UNESCAPED_UNICODE);

?>