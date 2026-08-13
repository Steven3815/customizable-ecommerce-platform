<?php

// 刪除購物車商品

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

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

// 檢查 JSON
if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查必要欄位
if (
    !isset($data["cart_item_id"]) ||
    !isset($data["store_id"])
) {
    echo json_encode([
        "error" => "Cart item ID and store ID are required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cart_item_id = $data["cart_item_id"];
$store_id = $data["store_id"];

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

// 檢查 store_id
if (
    !is_numeric($store_id) ||
    floor($store_id) != $store_id ||
    (int)$store_id <= 0
) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$store_id = (int)$store_id;

// 檢查購物車商品是否屬於目前會員以及指定商店
$sql = "
SELECT
    ci.cart_item_id
FROM CART_ITEM ci
JOIN CART c
    ON ci.cart_id = c.cart_id
WHERE ci.cart_item_id = ?
AND ci.store_id = ?
AND c.customer_id = ?
AND c.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $cart_item_id,
    $store_id,
    $customer_id,
    $store_id
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
AND cart_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $cart_item_id,
    $store_id
]);

// 回傳
echo json_encode([
    "message" => "Cart item deleted",
    "cart_item_id" => $cart_item_id,
    "store_id" => $store_id
], JSON_UNESCAPED_UNICODE);

?>