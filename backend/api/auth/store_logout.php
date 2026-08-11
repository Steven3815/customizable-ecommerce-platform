<?php

// Store 登出

header("Content-Type: application/json; charset=UTF-8");

session_start();

// 檢查是否為 Store
if (
    !isset($_SESSION["store_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "store"
) {
    echo json_encode([
        "error" => "Not logged in"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 清除所有 Session 資料
$_SESSION = [];

// 刪除 Session Cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 結束 Session
session_destroy();

// 回傳
echo json_encode([
    "message" => "Store logged out successfully"
], JSON_UNESCAPED_UNICODE);

?>