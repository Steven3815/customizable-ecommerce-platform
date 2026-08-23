<?php

// Customer 註冊

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON
if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["store_id"]) ||
    !isset($data["name"]) ||
    !isset($data["email"]) ||
    !isset($data["password"])
) {
    echo json_encode([
        "error" => "Store ID, name, email and password are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$data["store_id"];
$name = trim($data["name"]);
$email = trim($data["email"]);
$password = $data["password"];

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查姓名
if ($name === "") {
    echo json_encode([
        "error" => "Name is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Email
if ($email === "") {
    echo json_encode([
        "error" => "Email is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Email 格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "error" => "Please enter a valid email address"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 統一 Email 大小寫
$email = strtolower($email);

// 檢查密碼
if ($password === "") {
    echo json_encode([
        "error" => "Password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 密碼至少 8 碼
if (strlen($password) < 8) {
    echo json_encode([
        "error" => "Password must be at least 8 characters"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Store
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

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

// Store 不存在
if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 帳號停用
if ($store["store_status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式禁止註冊
if ($store["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Registration is unavailable in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Email 是否已註冊
$sql = "
SELECT customer_id
FROM CUSTOMER
WHERE email = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $email
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// 已經註冊
if ($customer) {
    echo json_encode([
        "error" => "Email already registered",
        "action" => "login"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 密碼加密
$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// 建立會員
$sql = "
INSERT INTO CUSTOMER
(
    name,
    email,
    password,
    created_at,
    updated_at
)
VALUES
(
    ?,
    ?,
    ?,
    NOW(),
    NOW()
)
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $email,
        $password_hash
    ]);

    $customer_id = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    // 處理 Email UNIQUE 衝突
    if ($e->getCode() === "23000") {
        echo json_encode([
            "error" => "Email already registered",
            "action" => "login"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
    echo json_encode([
        "error" => "Registration failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 註冊成功
echo json_encode([
    "message" => "Customer registered successfully",
    "customer_id" => $customer_id,
    "email" => $email,
    "store_id" => $store_id,
    "action" => "login"
], JSON_UNESCAPED_UNICODE);

?>