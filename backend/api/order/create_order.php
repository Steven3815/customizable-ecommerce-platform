<?php

// 建立訂單
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (
    !isset($data["customer_id"]) ||
    !isset($data["cart_item_ids"]) ||
    !isset($data["receiver_name"]) ||
    !isset($data["receiver_phone"]) ||
    !isset($data["receiver_address"]) ||
    !isset($data["delivery_method"])
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = $data["customer_id"];
$cart_item_ids = $data["cart_item_ids"];
$receiver_name = $data["receiver_name"];
$receiver_phone = $data["receiver_phone"];
$receiver_address = $data["receiver_address"];
$delivery_method = $data["delivery_method"];


// 檢查 cart_item_ids 是否為陣列
if (!is_array($cart_item_ids) || empty($cart_item_ids)) {
    echo json_encode([
        "error" => "Cart item IDs are required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 固定運費
$shipping_fee = 60;

// 開始交易
$pdo->beginTransaction();

try {
    // 1. 檢查會員
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
        throw new Exception("Customer not found");
    }

    // 2. 找會員購物車
    $sql = "
    SELECT cart_id
    FROM CART
    WHERE customer_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id
    ]);

    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        throw new Exception("Cart is empty");
    }

    $cart_id = $cart["cart_id"];

    // 3. 建立 IN (?, ?, ?)

    $placeholders = implode(
        ",",
        array_fill(0, count($cart_item_ids), "?")
    );

    // 4. 取得「選取的」購物車商品
    $sql = "
    SELECT
        ci.cart_item_id,
        ci.product_id,
        ci.spec_id,
        ci.quantity,

        p.product_name,
        p.has_spec,
        p.price AS product_price,

        ps.spec_name,
        ps.price AS spec_price
    FROM CART_ITEM ci
    JOIN PRODUCT p
        ON ci.product_id = p.product_id
    LEFT JOIN PRODUCT_SPEC ps
        ON ci.spec_id = ps.spec_id
    WHERE ci.cart_id = ?
    AND ci.cart_item_id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $params = array_merge(
        [$cart_id],
        $cart_item_ids
    );

    $stmt->execute($params);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. 檢查選取商品
    if (!$items) {
        throw new Exception("Selected cart items not found");
    }

    // 防止使用者傳入不存在於自己購物車的 cart_item_id
    if (count($items) != count($cart_item_ids)) {
        throw new Exception("Invalid cart item selected");
    }

    // 6. 計算商品總額
    $product_amount = 0;

    foreach ($items as $item) {

        // 有規格 → 使用規格價格
        if ($item["has_spec"] == 1) {

            $price = $item["spec_price"];

        } else {

            // 沒有規格 → 使用商品價格
            $price = $item["product_price"];
        }


        $product_amount +=
            $price * $item["quantity"];
    }

    // 7. 計算訂單總額
    $total_amount =
        $product_amount + $shipping_fee;

    // 8. 建立 ORDERS
    $sql = "
    INSERT INTO ORDERS
    (
        customer_id,
        receiver_name,
        receiver_phone,
        receiver_address,
        order_date,
        product_amount,
        shipping_fee,
        total_amount,
        delivery_method,
        delivery_status,
        estimated_ship_date,
        estimated_arrival_date
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        NOW(),
        ?,
        ?,
        ?,
        ?,
        'pending',
        DATE_ADD(CURDATE(), INTERVAL 3 DAY),
        DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id,
        $receiver_name,
        $receiver_phone,
        $receiver_address,
        $product_amount,
        $shipping_fee,
        $total_amount,
        $delivery_method
    ]);

    // 取得新訂單 ID
    $order_id = $pdo->lastInsertId();

    // 9. 建立 ORDER_ITEM
    foreach ($items as $item) {
        // 判斷價格
        if ($item["has_spec"] == 1) {

            $price = $item["spec_price"];

        } else {

            $price = $item["product_price"];
        }

        $sql = "
        INSERT INTO ORDER_ITEM
        (
            order_id,
            product_id,
            spec_id,
            product_name,
            spec_name,
            quantity,
            price
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $order_id,
            $item["product_id"],
            $item["spec_id"],
            $item["product_name"],
            $item["spec_name"],
            $item["quantity"],
            $price
        ]);
    }

    // 10. 只刪除已結帳商品
    $sql = "
    DELETE FROM CART_ITEM
    WHERE cart_id = ?
    AND cart_item_id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $params = array_merge(
        [$cart_id],
        $cart_item_ids
    );

    $stmt->execute($params);


    // 11. 完成交易
    $pdo->commit();

    // 12. 回傳
    echo json_encode([
        "message" => "Order created successfully",
        "order_id" => $order_id,
        "product_amount" => $product_amount,
        "shipping_fee" => $shipping_fee,
        "total_amount" => $total_amount
    ], JSON_UNESCAPED_UNICODE);


} catch (Exception $e) {

    // 發生錯誤 → 回復交易
    $pdo->rollBack();
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>