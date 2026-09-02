<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/cors.php";
require_once "../../middleware/store_auth.php";

echo json_encode([
    "authenticated" => true
], JSON_UNESCAPED_UNICODE);

?>