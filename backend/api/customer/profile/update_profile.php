<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

if (
    !isset($_SESSION["customer_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = (int)$_SESSION["customer_id"];

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

if (
    array_key_exists("email", $data) ||
    array_key_exists("password", $data)
) {
    echo json_encode([
        "error" => "Email and password cannot be modified"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$allowed_fields = [
    "name",
    "phone",
    "address",
    "preferred_payment",
    "preferred_delivery"
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
    customer_id,
    name,
    email,
    password,
    phone,
    address,
    preferred_payment,
    preferred_delivery
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$name = $customer["name"];
$phone = $customer["phone"];
$address = $customer["address"];
$preferred_payment = $customer["preferred_payment"];
$preferred_delivery = $customer["preferred_delivery"];

if (array_key_exists("name", $data)) {
    $name = trim($data["name"]);

    if ($name === "") {
        echo json_encode([
            "error" => "Name cannot be empty"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if (array_key_exists("phone", $data)) {
    $phone = trim($data["phone"]);
}

if (array_key_exists("address", $data)) {
    $address = trim($data["address"]);
}

if (array_key_exists("preferred_payment", $data)) {
    $preferred_payment = trim($data["preferred_payment"]);
}

if (array_key_exists("preferred_delivery", $data)) {
    $preferred_delivery = trim($data["preferred_delivery"]);
}

$sql = "
UPDATE CUSTOMER
SET
    name = ?,
    phone = ?,
    address = ?,
    preferred_payment = ?,
    preferred_delivery = ?
WHERE customer_id = ?
";

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $phone,
        $address,
        $preferred_payment,
        $preferred_delivery,
        $customer_id
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
    "message" => "Profile updated successfully",
    "customer" => [
        "customer_id" => $customer_id,
        "name" => $name,
        "email" => $customer["email"],
        "phone" => $phone,
        "address" => $address,
        "preferred_payment" => $preferred_payment,
        "preferred_delivery" => $preferred_delivery
    ]
], JSON_UNESCAPED_UNICODE);

?>