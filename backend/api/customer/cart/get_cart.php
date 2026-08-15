<?php

// Customer 取得購物車內容

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

// 檢查 store_id
if (!isset($data["store_id"])) {
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $data["store_id"];

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

// 檢查 Store
$sql = "
SELECT
    store_id,
    status
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store 必須為 active
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

if (!$store_setting) {
    echo json_encode([
        "error" => "Store setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不可使用購物車
if ($store_setting["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 找會員指定商店的購物車
$sql = "
SELECT
    cart_id
FROM CART
WHERE customer_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id,
    $store_id
]);

$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    echo json_encode([
        "message" => "Cart is empty",
        "store_id" => $store_id,
        "items" => [],
        "total_amount" => 0
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$cart_id = (int)$cart["cart_id"];

// 取得購物車商品
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
    AND ci.store_id = p.store_id

LEFT JOIN PRODUCT_SPEC ps
    ON ci.spec_id = ps.spec_id
    AND ci.product_id = ps.product_id
    AND ci.store_id = ps.store_id

WHERE ci.cart_id = ?
AND ci.store_id = ?
AND p.store_id = ?
AND p.status = 'active'

AND (
    p.has_spec = 0
    OR ps.status = 'active'
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $cart_id,
    $store_id,
    $store_id
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 計算總金額
$total_amount = 0;

foreach ($items as &$item) {

    $item["cart_item_id"] = (int)$item["cart_item_id"];
    $item["product_id"] = (int)$item["product_id"];
    $item["quantity"] = (int)$item["quantity"];

    $item["spec_id"] =
        $item["spec_id"] !== null
            ? (int)$item["spec_id"]
            : null;

    $item["price"] =
        $item["price"] !== null
            ? (float)$item["price"]
            : null;

    $item["subtotal"] =
        $item["subtotal"] !== null
            ? (float)$item["subtotal"]
            : null;

    if ($item["subtotal"] !== null) {
        $total_amount += $item["subtotal"];
    }
}

unset($item);

// 回傳
echo json_encode([
    "cart_id" => $cart_id,
    "store_id" => $store_id,
    "items" => $items,
    "total_amount" => $total_amount
], JSON_UNESCAPED_UNICODE);

?>