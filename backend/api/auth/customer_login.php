<?php

// Customer 登入

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";
require_once "../../config/database.php";

session_start();

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON 格式
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "JSON 格式錯誤"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["store_id"]) ||
    !isset($data["email"]) ||
    !isset($data["password"])
) {

    http_response_code(400);
    echo json_encode([
        "error" => "商店 ID、Email 和密碼為必填"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得資料
$store_id = (int)$data["store_id"];
$email = trim($data["email"]);
$password = $data["password"];

// 檢查 Store ID
if ($store_id <= 0) {

    http_response_code(400);
    echo json_encode([
        "error" => "商店 ID 無效"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Email
if ($email === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Email 為必填"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 統一 Email 大小寫
$email = strtolower($email);

// 檢查 Email 格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Email格式錯誤"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查密碼
if ($password === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "密碼為必填"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status AS store_status,
    ss.store_mode

FROM STORE s

INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

// Store 不存在
if (!$store) {
    http_response_code(404);
    echo json_encode([
        "error" => "找不到此商店"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 帳號停用
if ($store["store_status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "此商店目前已停用"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式禁止登入
if ($store["store_mode"] !== "shopping") {
    http_response_code(403);
    echo json_encode([
        "error" => "展示模式無法登入"
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
    http_response_code(401);
    echo json_encode([
        "error" => "Email帳號或密碼錯誤"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證密碼
if (!password_verify(
    $password,
    $customer["password"]
)) {
    http_response_code(401);
    echo json_encode([
        "error" => "Email帳號或密碼錯誤"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查目前已登入自己或其他 Customer
if (isset($_SESSION["customer_id"])) {

    if ($_SESSION["customer_id"] == (int)$customer["customer_id"]) {
        $error = "您已登入";
    } else {
        $error = "請先登出目前帳號";
    }

    http_response_code(409);
    echo json_encode([
        "error" => $error
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

    "store" => [
        "store_id" => $store_id,
        "store_name" => $store["store_name"]
    ],

    "session" => [
        "session_id" => session_id(),
        "customer_id" => $_SESSION["customer_id"],
        "role" => $_SESSION["role"],
    ]

], JSON_UNESCAPED_UNICODE);

?>