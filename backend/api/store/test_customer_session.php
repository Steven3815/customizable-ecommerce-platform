<?php

session_start();

$_SESSION = [];

$_SESSION["customer_id"] = 1;
$_SESSION["role"] = "customer";

echo json_encode([
    "session_id" => session_id(),
    "cookie" => $_COOKIE["PHPSESSID"] ?? null,
    "customer_id" => $_SESSION["customer_id"],
    "role" => $_SESSION["role"]
], JSON_UNESCAPED_UNICODE);

?>