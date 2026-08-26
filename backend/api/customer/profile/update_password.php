<?php

// Customer 修改密碼

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

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
    !isset($data["current_password"]) ||
    !isset($data["new_password"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Current password and new password are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$current_password = $data["current_password"];
$new_password = $data["new_password"];

// 檢查目前密碼
if ($current_password === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Current password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查新密碼
if ($new_password === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "New password is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 新密碼至少 8 碼
if (strlen($new_password) < 8) {
    http_response_code(400);
    echo json_encode([
        "error" => "New password must be at least 8 characters"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得會員目前密碼
$sql = "
SELECT
    customer_id,
    password
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到會員
if (!$customer) {
    http_response_code(404);
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 驗證目前密碼
if (!password_verify(
    $current_password,
    $customer["password"]
)) {
    http_response_code(401);
    echo json_encode([
        "error" => "Current password is incorrect"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 不允許新密碼與舊密碼相同
if (password_verify(
    $new_password,
    $customer["password"]
)) {
    http_response_code(409);
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

// 更新密碼
$sql = "
UPDATE CUSTOMER
SET
    password = ?,
    updated_at = NOW()
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $new_password_hash,
    $customer_id
]);

// 更新成功
echo json_encode([
    "message" => "Password updated successfully"
], JSON_UNESCAPED_UNICODE);

?>