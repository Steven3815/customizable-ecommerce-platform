<?php

// Customer 會員中心取得所有 Store 的退款列表

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得該 Customer 在所有 Store 的退款資料
$sql = "
SELECT
    r.refund_id,
    r.order_id,
    r.store_id,
    r.refund_reason,
    r.refund_description,
    r.refund_image_url,
    r.refund_status,
    r.admin_reply,
    r.requested_at,
    r.processed_at,

    s.store_name,

    o.order_number,
    o.total_amount,

    p.paid_at

FROM REFUND r

INNER JOIN ORDERS o
    ON r.order_id = o.order_id
    AND r.store_id = o.store_id

INNER JOIN STORE s
    ON r.store_id = s.store_id

LEFT JOIN PAYMENT p
    ON r.order_id = p.order_id
    AND r.store_id = p.store_id

WHERE o.customer_id = ?

ORDER BY r.requested_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $customer_id
]);

$refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$refunds) {
    echo json_encode([
        "count" => 0,
        "refunds" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理退款資料
$result = [];

foreach ($refunds as $refund) {

    $order_id = (int)$refund["order_id"];
    $refund_store_id = (int)$refund["store_id"];

    // 取得該訂單、該店家的商品明細
    $sql = "
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.spec_id,
        oi.product_name,
        oi.spec_name,
        oi.quantity,
        oi.price

    FROM ORDER_ITEM oi

    WHERE oi.order_id = ?
    AND oi.store_id = ?

    ORDER BY oi.order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $refund_store_id
    ]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理商品資料
    foreach ($items as &$item) {
        $item["order_item_id"] = (int)$item["order_item_id"];
        $item["product_id"] = (int)$item["product_id"];

        if ($item["spec_id"] !== null) {
            $item["spec_id"] = (int)$item["spec_id"];
        }

        $item["quantity"] = (int)$item["quantity"];
        $item["price"] = (float)$item["price"];
        $item["subtotal"] = $item["quantity"] * $item["price"];
    }

    unset($item);

    // 整理退款資料
    $refund_id = (int)$refund["refund_id"];
    $total_amount = (float)$refund["total_amount"];

    // 整理回傳格式
    $result[] = [
        "refund_id" => $refund_id,

        "store" => [
            "store_id" => $refund_store_id,
            "store_name" => $refund["store_name"]
        ],

        "order" => [
            "order_id" => $order_id,
            "order_number" => $refund["order_number"],
            "total_amount" => $total_amount
        ],

        "payment" => [
            "paid_at" => $refund["paid_at"]
        ],

        "items" => $items,

        "refund_reason" => $refund["refund_reason"],
        "refund_description" => $refund["refund_description"],
        "refund_image_url" => $refund["refund_image_url"],
        "refund_status" => $refund["refund_status"],
        "admin_reply" => $refund["admin_reply"],
        "requested_at" => $refund["requested_at"],
        "processed_at" => $refund["processed_at"]
    ];
}

// 回傳
echo json_encode([
    "message" => "Refunds retrieved successfully",
    "customer_id" => $customer_id,
    "count" => count($result),
    "refunds" => $result

], JSON_UNESCAPED_UNICODE);

?>