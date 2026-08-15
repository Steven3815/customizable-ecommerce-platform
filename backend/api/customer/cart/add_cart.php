<?php

// Customer 加入購物車

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

// 檢查必要欄位
if (
    !isset($data["store_id"]) ||
    !isset($data["product_id"]) ||
    !isset($data["quantity"])
) {
    echo json_encode([
        "error" => "Store ID, product ID and quantity are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $data["store_id"];
$product_id = $data["product_id"];
$spec_id = $data["spec_id"] ?? null;
$quantity = $data["quantity"];

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

// 檢查 product_id
if (
    !is_numeric($product_id) ||
    floor($product_id) != $product_id ||
    (int)$product_id <= 0
) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = (int)$product_id;

// 檢查 spec_id
if ($spec_id !== null) {

    if (
        !is_numeric($spec_id) ||
        floor($spec_id) != $spec_id ||
        (int)$spec_id <= 0
    ) {
        echo json_encode([
            "error" => "Invalid specification ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $spec_id = (int)$spec_id;
}

// 檢查數量
if (
    !is_numeric($quantity) ||
    floor($quantity) != $quantity ||
    (int)$quantity <= 0
) {
    echo json_encode([
        "error" => "Invalid quantity"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$quantity = (int)$quantity;

// 檢查會員
$sql = "
SELECT customer_id
FROM CUSTOMER
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

// 展示模式不可加入購物車
if ($store_setting["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 查詢商品
// 必須同時符合 store_id + product_id
$sql = "
SELECT
    product_id,
    store_id,
    has_spec,
    stock,
    status
FROM PRODUCT
WHERE product_id = ?
AND store_id = ?
AND status = 'active'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $product_id,
    $store_id
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        "error" => "Product does not belong to this store or product not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 有規格商品
if ((int)$product["has_spec"] === 1) {

    // 有規格商品必須傳 spec_id
    if ($spec_id === null) {
        echo json_encode([
            "error" => "Spec ID is required"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 檢查規格是否同時屬於
    // Store + Product
    $sql = "
    SELECT
        spec_id,
        stock
    FROM PRODUCT_SPEC
    WHERE spec_id = ?
    AND product_id = ?
    AND store_id = ?
    AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $spec_id,
        $product_id,
        $store_id
    ]);

    $spec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$spec) {
        echo json_encode([
            "error" => "Invalid specification"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 檢查規格庫存
    if ((int)$spec["stock"] < $quantity) {
        echo json_encode([
            "error" => "Insufficient stock"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

} else {

    // 無規格商品不可傳 spec_id
    if ($spec_id !== null) {
        echo json_encode([
            "error" => "This product does not have specifications"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 檢查商品庫存
    if ((int)$product["stock"] < $quantity) {
        echo json_encode([
            "error" => "Insufficient stock"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 找會員該 Store 的購物車
$sql = "
SELECT cart_id
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

    // 沒有購物車 → 建立購物車
    $sql = "
    INSERT INTO CART
    (
        customer_id,
        store_id
    )
    VALUES
    (
        ?,
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $customer_id,
        $store_id
    ]);

    $cart_id = (int)$pdo->lastInsertId();

} else {

    $cart_id = (int)$cart["cart_id"];
}

// 檢查購物車是否已有相同商品
$sql = "
SELECT
    cart_item_id,
    quantity
FROM CART_ITEM
WHERE cart_id = ?
AND store_id = ?
AND product_id = ?
AND (
    (spec_id IS NULL AND ? IS NULL)
    OR spec_id = ?
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $cart_id,
    $store_id,
    $product_id,
    $spec_id,
    $spec_id
]);

$cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

// 已存在 → 增加數量
if ($cart_item) {

    $new_quantity =
        (int)$cart_item["quantity"] + $quantity;

    if ((int)$product["has_spec"] === 1) {
        $available_stock = (int)$spec["stock"];
    } else {
        $available_stock = (int)$product["stock"];
    }

    if ($new_quantity > $available_stock) {
        echo json_encode([
            "error" => "Insufficient stock"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $sql = "
    UPDATE CART_ITEM
    SET quantity = ?
    WHERE cart_item_id = ?
    AND cart_id = ?
    AND store_id = ?
    AND product_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $new_quantity,
        $cart_item["cart_item_id"],
        $cart_id,
        $store_id,
        $product_id
    ]);

    $message = "Cart quantity updated";

    $final_quantity = $new_quantity;

} else {

    // 不存在 → 新增
    $sql = "
    INSERT INTO CART_ITEM
    (
        cart_id,
        store_id,
        product_id,
        spec_id,
        quantity
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $cart_id,
        $store_id,
        $product_id,
        $spec_id,
        $quantity
    ]);

    $message = "Product added to cart";

    $final_quantity = $quantity;
}

// 回傳
echo json_encode([
    "message" => $message,
    "customer_id" => $customer_id,
    "cart_id" => $cart_id,
    "store_id" => $store_id,
    "product_id" => $product_id,
    "spec_id" => $spec_id,
    "quantity" => $final_quantity
], JSON_UNESCAPED_UNICODE);

?>