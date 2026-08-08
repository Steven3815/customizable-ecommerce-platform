<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

// 檢查欄位
if (
    !isset($data["cart_item_id"]) ||
    !isset($data["quantity"])||
    !isset($data["customer_id"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ],
    JSON_UNESCAPED_UNICODE);
    exit;
}

$cart_item_id = $data["cart_item_id"];
$quantity = $data["quantity"];
$customer_id = $data["customer_id"];

// 檢查商品是否存在
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

if (!$item) {
    echo json_encode([
        "error" => "Cart item not found"
    ],
    JSON_UNESCAPED_UNICODE);
    exit;
}

// 更新數量
$sql = "
UPDATE CART_ITEM
SET quantity = ?
WHERE cart_item_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $quantity,
    $cart_item_id
]);

echo json_encode([
    "message" => "Cart updated"
],
JSON_UNESCAPED_UNICODE);

?>