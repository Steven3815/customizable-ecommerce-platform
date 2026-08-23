<?php

// Store 修改密碼

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Store Session
if (
    !isset($_SESSION["store_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "store"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$_SESSION["store_id"];

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否存在
$sql = "
SELECT
    store_id
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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
    !isset($data["current_password"]) ||
    !isset($data["new_password"])
) {
    echo json_encode([
        "error" => "Current password and new password are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$current_password = $data["current_password"];
$new_password = $data["new_password"];

// 檢查目前密碼
if ($current_password === "") {
    echo json_encode([
        "error" => "Current password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查新密碼
if ($new_password === "") {
    echo json_encode([
        "error" => "New password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 新密碼至少 8 碼
if (strlen($new_password) < 8) {
    echo json_encode([
        "error" => "New password must be at least 8 characters"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得商家目前密碼
$sql = "
SELECT
    store_id,
    password
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到商家
if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證目前密碼
if (!password_verify(
    $current_password,
    $store["password"]
)) {
    echo json_encode([
        "error" => "Current password is incorrect"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 不允許新密碼與舊密碼相同
if (password_verify(
    $new_password,
    $store["password"]
)) {
    echo json_encode([
        "error" => "New password must be different from current password"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 新密碼加密
$new_password_hash = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);

if ($new_password_hash === false) {
    echo json_encode([
        "error" => "Password encryption failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新密碼
$sql = "
UPDATE STORE
SET
    password = ?,
    updated_at = NOW()
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $new_password_hash,
    $store_id
]);

// 更新成功
echo json_encode([
    "message" => "Password updated successfully"
], JSON_UNESCAPED_UNICODE);

?>