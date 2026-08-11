<?php

// 刪除購物車商品
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

session_start();

// 檢查 Customer Session
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

// 檢查 cart_item_id
if (!isset($data["cart_item_id"])) {
    echo json_encode([
        "error" => "Cart item ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cart_item_id = $data["cart_item_id"];

// 檢查 cart_item_id
if (
    !is_numeric($cart_item_id) ||
    floor($cart_item_id) != $cart_item_id ||
    (int)$cart_item_id <= 0
) {
    echo json_encode([
        "error" => "Invalid cart item ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cart_item_id = (int)$cart_item_id;

// 檢查購物車商品是否屬於目前會員
$sql = "
SELECT ci.cart_item_id
FROM CART_ITEM ci
JOIN CART c
    ON ci.cart_id = c.cart_id
WHERE ci.cart_item_id = ?
AND c.customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $cart_item_id,
    $customer_id
]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

// 找不到商品
if (!$item) {
    echo json_encode([
        "error" => "Cart item not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 刪除商品
$sql = "
DELETE FROM CART_ITEM
WHERE cart_item_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $cart_item_id
]);

// 回傳
echo json_encode([
    "message" => "Cart item deleted",
    "cart_item_id" => $cart_item_id
], JSON_UNESCAPED_UNICODE);

?>