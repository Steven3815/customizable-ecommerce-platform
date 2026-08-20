<?php

// Customer 更新購物車商品數量

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
    !isset($data["quantity"]) ||
    !isset($data["store_id"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$cart_item_id = $data["cart_item_id"];
$quantity = $data["quantity"];
$store_id = $data["store_id"];

// 檢查 Cart Item ID
if (
    !is_numeric($cart_item_id) ||
    floor((float)$cart_item_id) != (float)$cart_item_id ||
    (int)$cart_item_id <= 0
) {
    echo json_encode([
        "error" => "Invalid cart item ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$cart_item_id = (int)$cart_item_id;

// 檢查 Store ID
if (
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$store_id;

// 檢查數量
if (
    !is_numeric($quantity) ||
    floor((float)$quantity) != (float)$quantity ||
    (int)$quantity <= 0
) {
    echo json_encode([
        "error" => "Invalid quantity"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$quantity = (int)$quantity;

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

// 展示模式不可更新購物車
if ($store_setting["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得購物車商品
$sql = "
SELECT
    ci.cart_item_id,
    ci.cart_id,
    ci.product_id,
    ci.spec_id,
    ci.store_id,

    p.product_name,
    p.has_spec,
    p.stock AS product_stock,
    p.status AS product_status,
    p.category_id,

    c.category_name,
    c.status AS category_status,

    ps.stock AS spec_stock,
    ps.status AS spec_status

FROM CART_ITEM ci

JOIN CART cart
    ON ci.cart_id = cart.cart_id
    AND ci.store_id = cart.store_id

JOIN PRODUCT p
    ON ci.product_id = p.product_id
    AND ci.store_id = p.store_id

LEFT JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND p.store_id = c.store_id

LEFT JOIN PRODUCT_SPEC ps
    ON ci.spec_id = ps.spec_id
    AND ci.product_id = ps.product_id
    AND ci.store_id = ps.store_id

WHERE ci.cart_item_id = ?
AND ci.store_id = ?
AND cart.customer_id = ?
AND cart.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $cart_item_id,
    $store_id,
    $customer_id,
    $store_id
]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode([
        "error" => "Cart item not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商品必須為 active
if ($item["product_status"] !== "active") {
    echo json_encode([
        "error" => "Product is not available"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Category 必須存在
if ($item["category_id"] === null) {
    echo json_encode([
        "error" => "Product category not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Category 必須為 active
if ($item["category_status"] !== "active") {
    echo json_encode([
        "error" => "Product category is not available"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 判斷庫存
if ((int)$item["has_spec"] === 1) {

    // 有規格商品
    if ($item["spec_id"] === null) {
        echo json_encode([
            "error" => "Product specification is required"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 規格必須為 active
    if ($item["spec_status"] !== "active") {
        echo json_encode([
            "error" => "Product specification is not available"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $available_stock = (int)$item["spec_stock"];

} else {

    // 無規格商品不可有 spec_id
    if ($item["spec_id"] !== null) {
        echo json_encode([
            "error" => "Invalid product specification"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $available_stock = (int)$item["product_stock"];
}

// 檢查庫存
if ($quantity > $available_stock) {
    echo json_encode([
        "error" => "Insufficient stock",
        "available_stock" => $available_stock
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 更新購物車數量
$sql = "
UPDATE CART_ITEM
SET
    quantity = ?
WHERE cart_item_id = ?
AND cart_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $quantity,
    $cart_item_id,
    $item["cart_id"],
    $store_id
]);

// 確認是否成功更新
if ($stmt->rowCount() !== 1) {
    echo json_encode([
        "error" => "Cart quantity could not be updated"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 回傳
echo json_encode([
    "message" => "Cart updated",
    "customer_id" => $customer_id,
    "cart_item_id" => $cart_item_id,
    "store_id" => $store_id,
    "product_id" => (int)$item["product_id"],
    "spec_id" =>
        $item["spec_id"] !== null
            ? (int)$item["spec_id"]
            : null,
    "quantity" => $quantity
], JSON_UNESCAPED_UNICODE);

?>