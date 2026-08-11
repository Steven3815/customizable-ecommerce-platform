<?php

// Customer 建立訂單

header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

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

// 取得 JSON 資料
$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON data"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查必要欄位
if (
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

$cart_item_ids = $data["cart_item_ids"];
$receiver_name = trim($data["receiver_name"]);
$receiver_phone = trim($data["receiver_phone"]);
$receiver_address = trim($data["receiver_address"]);
$delivery_method = trim($data["delivery_method"]);

// 檢查購物車商品
if (!is_array($cart_item_ids) || empty($cart_item_ids)) {
    echo json_encode([
        "error" => "Cart item IDs are required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查收件資料
if (
    $receiver_name === "" ||
    $receiver_phone === "" ||
    $receiver_address === ""
) {
    echo json_encode([
        "error" => "Receiver information is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查 cart_item_id
foreach ($cart_item_ids as $cart_item_id) {
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
}

$cart_item_ids = array_map("intval", $cart_item_ids);

// 移除重複的 cart_item_id
$cart_item_ids = array_values(
    array_unique($cart_item_ids)
);

// 目前先固定運費
$shipping_fee = 60;

// 允許的配送方式
$allowed_delivery_methods = [
    "home_delivery"
];

if (!in_array(
    $delivery_method,
    $allowed_delivery_methods,
    true
)) {
    echo json_encode([
        "error" => "Invalid delivery method"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 建立 IN (?, ?, ?)
$placeholders = implode(
    ",",
    array_fill(0, count($cart_item_ids), "?")
);

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
    $stmt->execute([$customer_id]);

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
    $stmt->execute([$customer_id]);

    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        throw new Exception("Cart not found");
    }

    $cart_id = (int)$cart["cart_id"];

    // 3. 取得選取的購物車商品
    $sql = "
    SELECT
        ci.cart_item_id,
        ci.product_id,
        ci.spec_id,
        ci.quantity,

        p.product_name,
        p.has_spec,
        p.price AS product_price,
        p.stock AS product_stock,
        p.status AS product_status,

        ps.spec_name,
        ps.price AS spec_price,
        ps.stock AS spec_stock,
        ps.status AS spec_status

    FROM CART_ITEM ci

    INNER JOIN PRODUCT p
        ON ci.product_id = p.product_id

    LEFT JOIN PRODUCT_SPEC ps
        ON ci.spec_id = ps.spec_id
        AND ps.status = 'active'

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

    // 4. 檢查商品是否全部存在
    if (!$items) {
        throw new Exception(
            "Selected cart items not found"
        );
    }

    if (count($items) !== count($cart_item_ids)) {
        throw new Exception(
            "Invalid cart item selected"
        );
    }

    // 5. 檢查商品狀態、規格狀態與庫存
    $product_amount = 0;

    foreach ($items as $item) {
        $quantity = (int)$item["quantity"];

        if ($quantity <= 0) {
            throw new Exception(
                "Invalid product quantity"
            );
        }

        // 商品必須是 active
        if ($item["product_status"] !== "active") {
            throw new Exception(
                "Product is no longer available"
            );
        }

        // 有規格
        if ((int)$item["has_spec"] === 1) {
            if (
                $item["spec_id"] === null ||
                $item["spec_status"] !== "active"
            ) {
                throw new Exception(
                    "Product specification is no longer available"
                );
            }

            $price = (float)$item["spec_price"];
            $stock = (int)$item["spec_stock"];

            if ($quantity > $stock) {
                throw new Exception(
                    "Insufficient specification stock"
                );
            }
        } else {
            // 無規格
            $price = (float)$item["product_price"];
            $stock = (int)$item["product_stock"];

            if ($quantity > $stock) {
                throw new Exception(
                    "Insufficient product stock"
                );
            }
        }

        $product_amount += $price * $quantity;
    }

    // 6. 計算訂單總額
    $total_amount =
        $product_amount + $shipping_fee;

    // 7. 建立 ORDERS
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

    $order_id = (int)$pdo->lastInsertId();

    // 8. 建立 ORDER_ITEM
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

    $stmt_order_item = $pdo->prepare($sql);

    foreach ($items as $item) {
        if ((int)$item["has_spec"] === 1) {
            $price = (float)$item["spec_price"];
            $spec_name = $item["spec_name"];
        } else {
            $price = (float)$item["product_price"];
            $spec_name = null;
        }

        $stmt_order_item->execute([
            $order_id,
            $item["product_id"],
            $item["spec_id"],
            $item["product_name"],
            $spec_name,
            $item["quantity"],
            $price
        ]);
    }

    // 9. 扣除庫存
    foreach ($items as $item) {
        $quantity = (int)$item["quantity"];

        if ((int)$item["has_spec"] === 1) {
            $sql = "
            UPDATE PRODUCT_SPEC
            SET
                stock = stock - ?,
                updated_at = NOW()
            WHERE spec_id = ?
            AND product_id = ?
            AND status = 'active'
            AND stock >= ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $quantity,
                $item["spec_id"],
                $item["product_id"],
                $quantity
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "Failed to update specification stock"
                );
            }
        } else {
            $sql = "
            UPDATE PRODUCT
            SET
                stock = stock - ?,
                updated_at = NOW()
            WHERE product_id = ?
            AND status = 'active'
            AND stock >= ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $quantity,
                $item["product_id"],
                $quantity
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "Failed to update product stock"
                );
            }
        }
    }

    // 10. 刪除已結帳商品
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
        "customer_id" => $customer_id,
        "product_amount" => $product_amount,
        "shipping_fee" => $shipping_fee,
        "total_amount" => $total_amount,
        "delivery_method" => $delivery_method,
        "delivery_status" => "pending"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>