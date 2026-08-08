<?php
//購物車內容
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

if (!isset($_GET["customer_id"])) {

    echo json_encode([
        "error" => "Customer ID is required"
    ],
    JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = $_GET["customer_id"];

// 找購物車

$sql = "
SELECT cart_id
FROM CART
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);

$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {

    echo json_encode([
        "message" => "Cart is empty"
    ],
        JSON_UNESCAPED_UNICODE);
    exit;

}

$cart_id = $cart["cart_id"];

// 取得購物車商品
// 如果有規格用ps.price, 沒有規格用p.price
$sql = "
SELECT
    ci.cart_item_id,
    p.product_id,
    p.product_name,
    p.description,
    ci.quantity,
    CASE
        WHEN p.has_spec = 1
        THEN ps.price
        ELSE p.price
    END AS price,
    (
        ci.quantity *
        CASE
            WHEN p.has_spec = 1
            THEN ps.price
            ELSE p.price
        END
    ) AS subtotal,
    ps.spec_id,
    ps.spec_name
FROM CART_ITEM ci
JOIN PRODUCT p
ON ci.product_id = p.product_id
LEFT JOIN PRODUCT_SPEC ps
ON ci.spec_id = ps.spec_id
WHERE ci.cart_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$cart_id]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_amount = 0;

foreach ($items as &$item) {
    $item["cart_item_id"] = (int)$item["cart_item_id"];
    $item["product_id"] = (int)$item["product_id"];
    $item["quantity"] = (int)$item["quantity"];
    $item["price"] = (float)$item["price"];
    $item["subtotal"] = (float)$item["subtotal"];

    $total_amount += $item["subtotal"];
}

unset($item);
// 回傳

echo json_encode([
    "cart_id" => (int)$cart_id,
    "items" => $items,
    "total_amount" => $total_amount
], JSON_UNESCAPED_UNICODE);

?>