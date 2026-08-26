<?php

// Store 更新商家資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/store_auth.php";

// 取得目前 Store 資料
$sql = "
SELECT
    store_id,
    store_name,
    store_url,
    owner_name,
    email,
    phone,
    status
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

// 檢查 Store 是否存在
if (!$store) {
    http_response_code(404);
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 JSON 資料
$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Email 不允許修改
if (array_key_exists("email", $data)) {
    http_response_code(409);
    echo json_encode([
        "error" => "Email cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 允許修改的欄位
$allowed_fields = [
    "store_name",
    "owner_name",
    "phone"
];

// 檢查是否有要更新的欄位
$has_update = false;

foreach ($allowed_fields as $field) {

    if (array_key_exists($field, $data)) {
        $has_update = true;
        break;
    }
}

if (!$has_update) {
    http_response_code(400);
    echo json_encode([
        "error" => "No fields to update"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 使用原本資料
$store_name = $store["store_name"];
$owner_name = $store["owner_name"];
$phone = $store["phone"];

// 更新 Store Name
if (array_key_exists("store_name", $data)) {

    if (!is_string($data["store_name"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid store name"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $store_name = trim($data["store_name"]);

    if ($store_name === "") {
        http_response_code(400);
        echo json_encode([
            "error" => "Store name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (mb_strlen($store_name) > 100) {
        http_response_code(400);
        echo json_encode([
            "error" => "Store name is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 更新 Owner Name
if (array_key_exists("owner_name", $data)) {

    if (!is_string($data["owner_name"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid owner name"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $owner_name = trim($data["owner_name"]);

    if ($owner_name === "") {
        http_response_code(400);
        echo json_encode([
            "error" => "Owner name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (mb_strlen($owner_name) > 100) {
        http_response_code(400);
        echo json_encode([
            "error" => "Owner name is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 更新 Phone
if (array_key_exists("phone", $data)) {

    if (!is_string($data["phone"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid phone number"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $phone = trim($data["phone"]);

    if (mb_strlen($phone) > 30) {
        http_response_code(400);
        echo json_encode([
            "error" => "Phone number is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 更新 Store
$sql = "
UPDATE STORE
SET
    store_name = ?,
    owner_name = ?,
    phone = ?,
    updated_at = NOW()
WHERE store_id = ?
";

try {

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $store_name,
        $owner_name,
        $phone,
        $store_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        "error" => "Profile update failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳更新後資料
echo json_encode([
    "message" => "Store profile updated successfully",
    "store" => [
        "store_id" => (int)$store["store_id"],
        "store_name" => $store_name,
        "store_url" => $store["store_url"],
        "owner_name" => $owner_name,
        "email" => $store["email"],
        "phone" => $phone,
        "status" => $store["status"]
    ]
], JSON_UNESCAPED_UNICODE);

?>