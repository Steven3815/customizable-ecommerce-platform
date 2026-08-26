<?php

// STORE 商家註冊

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";
require_once "../../config/database.php";

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查 JSON 格式
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查必要欄位
if (
    !isset($data["store_name"]) ||
    !isset($data["owner_name"]) ||
    !isset($data["email"]) ||
    !isset($data["password"]) ||
    !isset($data["phone"]) ||
    !isset($data["store_mode"])
) {

    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得資料
$store_name = trim($data["store_name"]);
$owner_name = trim($data["owner_name"]);
$email = trim($data["email"]);
$password = $data["password"];
$phone = trim($data["phone"]);
$store_mode = trim($data["store_mode"]);

// 檢查商家名稱
if ($store_name === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Store name is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查負責人姓名
if ($owner_name === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Owner name is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Email
if ($email === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Email is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Email 統一小寫
$email = strtolower($email);

// 檢查 Email 格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid email format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查密碼
if ($password === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 密碼至少 8 碼
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        "error" => "Password must be at least 8 characters"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查電話
if ($phone === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Phone is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查商店模式
if (
    $store_mode !== "shopping" &&
    $store_mode !== "showcase"
) {

    http_response_code(400);
    echo json_encode([
        "error" => "Invalid store mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Email 是否已註冊
$sql = "
SELECT store_id
FROM STORE
WHERE email = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if ($store) {
    http_response_code(409);
    echo json_encode([
        "error" => "Email already registered",
        "action" => "login"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 密碼加密
$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);

try {
    $pdo->beginTransaction();

    // store_url 先暫時 NULL
    $sql = "
    INSERT INTO STORE
    (
        store_name,
        store_url,
        email,
        password,
        owner_name,
        phone,
        status,
        created_at,
        updated_at
    )
    VALUES
    (
        ?,
        NULL,
        ?,
        ?,
        ?,
        ?,
        'active',
        NOW(),
        NOW()
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_name,
        $email,
        $hashed_password,
        $owner_name,
        $phone
    ]);

    // 取得 store_id
    $store_id = (int)$pdo->lastInsertId();

    // 自動產生 store_url
    $store_url = "https://ecommerce.com/store-" . $store_id;

    // 更新 store_url
    $sql = "
    UPDATE STORE
    SET
        store_url = ?,
        updated_at = NOW()
    WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_url,
        $store_id
    ]);

    // 建立 STORE_SETTING
    // store_mode 使用註冊時選擇的模式
    $sql = "
    INSERT INTO STORE_SETTING
    (
        store_id,
        store_mode
    )
    VALUES
    (
        ?,
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id,
        $store_mode
    ]);

    // 建立 WEBSITE_SETTING
    // 如果其他欄位有 DEFAULT，就讓資料庫處理
    $sql = "
    INSERT INTO WEBSITE_SETTING
    (
        store_id
    )
    VALUES
    (
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    // 建立 HOMEPAGE_PRODUCT_SETTING
    $sql = "
    INSERT INTO HOMEPAGE_PRODUCT_SETTING
    (
        store_id
    )
    VALUES
    (
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    // 每個 Store 固定建立 5 種付款方式
    $sql = "
    INSERT INTO STORE_PAYMENT_METHOD
    (
        store_id,
        store_payment_id,
        payment_method,
        status
    )
    VALUES
    (?, 1, 'credit_card', 'inactive'),
    (?, 2, 'atm', 'inactive'),
    (?, 3, 'post_office', 'inactive'),
    (?, 4, 'cash_on_delivery', 'inactive'),
    (?, 5, 'in_store', 'inactive')
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_id,
        $store_id,
        $store_id,
        $store_id,
        $store_id
    ]);

    $pdo->commit();

    // 回傳
    echo json_encode([
        "message" => "Store registration successful",
        "action" => "login",
        "store" => [
            "store_id" => $store_id,
            "store_name" => $store_name,
            "store_url" => $store_url,
            "email" => $email,
            "owner_name" => $owner_name,
            "phone" => $phone,
            "status" => "active",
            "store_mode" => $store_mode
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 發生錯誤 → Rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "error" => "Store registration failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>