<?php

// Customer 登入狀態

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";

session_start();

if (
    isset($_SESSION["customer_id"]) &&
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "customer"
) {

    echo json_encode([
        "loggedIn" => true,
        "customer_id" => (int)$_SESSION["customer_id"]
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode([
    "loggedIn" => false
], JSON_UNESCAPED_UNICODE);

?>