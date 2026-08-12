<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (array_key_exists("email", $data)) {
    echo json_encode([
        "error" => "Email cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$allowed_fields = [
    "store_name",
    "owner_name",
    "phone"
];

$has_update = false;

foreach ($allowed_fields as $field) {
    if (array_key_exists($field, $data)) {
        $has_update = true;
        break;
    }
}

if (!$has_update) {
    echo json_encode([
        "error" => "No fields to update"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_name = $store["store_name"];
$owner_name = $store["owner_name"];
$phone = $store["phone"];

if (array_key_exists("store_name", $data)) {
    $store_name = trim($data["store_name"]);

    if ($store_name === "") {
        echo json_encode([
            "error" => "Store name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (mb_strlen($store_name) > 100) {
        echo json_encode([
            "error" => "Store name is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if (array_key_exists("owner_name", $data)) {
    $owner_name = trim($data["owner_name"]);

    if ($owner_name === "") {
        echo json_encode([
            "error" => "Owner name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (mb_strlen($owner_name) > 100) {
        echo json_encode([
            "error" => "Owner name is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if (array_key_exists("phone", $data)) {
    $phone = trim($data["phone"]);

    if (mb_strlen($phone) > 30) {
        echo json_encode([
            "error" => "Phone number is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

$sql = "
UPDATE STORE
SET
    store_name = ?,
    owner_name = ?,
    phone = ?
WHERE store_id = ?
";

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_name,
        $owner_name,
        $phone,
        $store_id
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => "Profile update failed"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode([
    "message" => "Store profile updated successfully",
    "store" => [
        "store_id" => $store_id,
        "store_name" => $store_name,
        "store_url" => "$store["store_url"],
        "owner_name" => $owner_name,
        "email" => $store["email"],
        "phone" => $phone,
        "status" => $store["status"]
    ]
], JSON_UNESCAPED_UNICODE);

?>