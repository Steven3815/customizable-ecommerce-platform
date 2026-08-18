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

// 檢查 Customer ID
if ($customer_id <= 0) {
    echo json_encode([
        "error" => "Invalid customer ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Customer 是否存在
$sql = "
SELECT
    customer_id
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 JSON
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

// 檢查 Store 模式
$sql = "
SELECT
    store_mode
FROM STORE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store_setting = $stmt->fetch(PDO::FETCH_ASSOC);

// Store Setting 不存在
if (!$store_setting) {
    echo json_encode([
        "error" => "Store setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不可刪除購物車商品
if ($store_setting["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查購物車商品是否屬於目前會員以及指定商店
$sql = "
SELECT
    ci.cart_item_id,
    ci.cart_id
FROM CART_ITEM ci

JOIN CART c
    ON ci.cart_id = c.cart_id
    AND ci.store_id = c.store_id

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

$cart_id = (int)$item["cart_id"];

// 刪除購物車商品
$sql = "
DELETE FROM CART_ITEM
WHERE cart_item_id = ?
AND cart_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $cart_item_id,
    $cart_id,
    $store_id
]);

// 確認是否成功刪除
if ($stmt->rowCount() !== 1) {
    echo json_encode([
        "error" => "Cart item could not be deleted"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "message" => "Cart item deleted",
    "cart_item_id" => $cart_item_id,
    "store_id" => $store_id
], JSON_UNESCAPED_UNICODE);

?>