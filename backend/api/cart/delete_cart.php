<?php
// 刪除購物車商品
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (
    !isset($data["customer_id"]) ||
    !isset($data["cart_item_id"])
) {

    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = $data["customer_id"];
$cart_item_id = $data["cart_item_id"];

// 檢查購物車商品是否屬於該會員
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

// 找不到
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

echo json_encode([
    "message" => "Cart item deleted"
], JSON_UNESCAPED_UNICODE);

?>