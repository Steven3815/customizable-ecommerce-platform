<?php
// 加入購物車
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (
    !isset($data["customer_id"]) ||
    !isset($data["product_id"]) ||
    !isset($data["quantity"])
) {

    echo json_encode([
        "error" => "Missing required fields"
    ]);

    exit;

}

$customer_id = $data["customer_id"];
$product_id = $data["product_id"];
$spec_id = $data["spec_id"] ?? null;
$quantity = $data["quantity"];

// 檢查數量
if (!is_numeric($quantity) || $quantity <= 0 || floor($quantity) != $quantity) {
    echo json_encode([
        "error" => "Invalid quantity"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

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

// 查詢商品
$sql = "
SELECT
    product_id,
    has_spec
FROM PRODUCT
WHERE product_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {

    echo json_encode(
        [
            "error" => "Product not found"
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;

}

// 檢查商品規格
if ($product["has_spec"] == 1) {
    // 有規格商品須提供 spec_id
    if ($spec_id === null) {
        echo json_encode(
            [
                "error" => "Spec ID is required"
            ],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
    // 檢查 spec 是否屬於該商品
    $sql = "
    SELECT
        spec_id
    FROM PRODUCT_SPEC
    WHERE spec_id = ?
    AND product_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $spec_id,
        $product_id
    ]);

    $spec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$spec) {
        echo json_encode(
            [
                "error" => "Invalid specification"
            ],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}

// 找會員購物車

$sql = "
SELECT cart_id
FROM CART
WHERE customer_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    // 沒有購物車建立

    $sql = "
    INSERT INTO CART(customer_id)
    VALUES(?)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);

    $cart_id = $pdo->lastInsertId();

} else {
    $cart_id = $cart["cart_id"];
}

// 加入商品
$sql = "
SELECT
    cart_item_id,
    quantity
FROM CART_ITEM
WHERE cart_id = ?
AND product_id = ?
AND (
    (spec_id IS NULL AND ? IS NULL)
    OR spec_id = ?
)
";// 如果兩邊(購物車內、新增)都沒有規格(因product_id相同)，或者兩邊的規格編號相同，就代表購物車已經有這個商品

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $cart_id,
    $product_id,
    $spec_id,
    $spec_id
]);

$cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

// 已存在商品 → 增加數量
if ($cart_item) {

    $sql = "
    UPDATE CART_ITEM
    SET quantity = quantity + ?
    WHERE cart_item_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $quantity,
        $cart_item["cart_item_id"]
    ]);


    $message = "Cart quantity updated";

// 不存在商品 → 新增商品
} else {
    $sql = "
    INSERT INTO CART_ITEM
    (
        cart_id,
        product_id,
        spec_id,
        quantity
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cart_id,
        $product_id,
        $spec_id,
        $quantity

    ]);

    $message = "Product added to cart";
}

echo json_encode([
    "message" => $message
],
JSON_UNESCAPED_UNICODE);

?>