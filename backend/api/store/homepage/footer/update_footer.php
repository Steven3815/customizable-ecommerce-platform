<?php

// Store 更新 Footer 設定

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";


// 檢查必要欄位
if (
    !isset($_POST["contact_phone"]) ||
    !isset($_POST["contact_phone_enable"]) ||
    !isset($_POST["address"]) ||
    !isset($_POST["address_enable"]) ||
    !isset($_POST["email"]) ||
    !isset($_POST["email_enable"]) ||
    !isset($_POST["service_phone"]) ||
    !isset($_POST["service_phone_enable"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$contact_phone = trim($_POST["contact_phone"]);
$contact_phone_enable = (int)$_POST["contact_phone_enable"];

$address = trim($_POST["address"]);
$address_enable = (int)$_POST["address_enable"];

$email = trim($_POST["email"]);
$email_enable = (int)$_POST["email_enable"];

$service_phone = trim($_POST["service_phone"]);
$service_phone_enable = (int)$_POST["service_phone_enable"];

// 檢查 Enable 欄位
$enable_fields = [
    "contact_phone_enable" => $contact_phone_enable,
    "address_enable" => $address_enable,
    "email_enable" => $email_enable,
    "service_phone_enable" => $service_phone_enable
];

foreach ($enable_fields as $field => $value) {
    if ($value !== 0 && $value !== 1) {
        echo json_encode([
            "error" => "Invalid " . $field
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 檢查 Email
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "error" => "Invalid email"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 Footer 設定
$sql = "
SELECT
    footer_id
FROM FOOTER_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$footer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$footer) {
    echo json_encode([
        "error" => "Footer setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新 Footer 設定
try {
    $sql = "
    UPDATE FOOTER_SETTING
    SET
        contact_phone = ?,
        contact_phone_enable = ?,
        address = ?,
        address_enable = ?,
        email = ?,
        email_enable = ?,
        service_phone = ?,
        service_phone_enable = ?,
        updated_at = NOW()
    WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $contact_phone,
        $contact_phone_enable,
        $address,
        $address_enable,
        $email,
        $email_enable,
        $service_phone,
        $service_phone_enable,
        $store_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => "Failed to update footer settings"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理資料
$contact_phone_enable = (bool)$contact_phone_enable;
$address_enable = (bool)$address_enable;
$email_enable = (bool)$email_enable;
$service_phone_enable = (bool)$service_phone_enable;

// 回傳
echo json_encode([
    "message" => "Footer settings updated successfully",
    "store_id" => $store_id,
    "footer" => [
        "contact_phone" => $contact_phone,
        "contact_phone_enable" => $contact_phone_enable,
        "address" => $address,
        "address_enable" => $address_enable,
        "email" => $email,
        "email_enable" => $email_enable,
        "service_phone" => $service_phone,
        "service_phone_enable" => $service_phone_enable
    ]
], JSON_UNESCAPED_UNICODE);

?>