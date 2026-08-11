<?php

session_start();

$_SESSION["store_id"] = 1;
$_SESSION["role"] = "store";

echo json_encode([
    "message" => "Session created",
    "store_id" => $_SESSION["store_id"],
    "role" => $_SESSION["role"]
], JSON_UNESCAPED_UNICODE);

?>