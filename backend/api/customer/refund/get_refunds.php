<?php

// 取得退款列表
header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Customer Session
if (!isset($_SESSION["customer_id"])) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = (int)$_SESSION["customer_id"];

// 取得退款資料
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
    o.total_amount,
    p.paid_at
FROM REFUND r
JOIN ORDERS o
    ON r.order_id = o.order_id
    AND r.store_id = o.store_id
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
        "message" => "目前無退款紀錄",
        "refunds" => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 整理每一筆退款
$result = [];

foreach ($refunds as $refund) {

    $order_id = (int)$refund["order_id"];
    $store_id = (int)$refund["store_id"];

    // 取得商品明細
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
    JOIN ORDERS o
        ON oi.order_id = o.order_id
        AND oi.store_id = o.store_id
    WHERE oi.order_id = ?
    AND oi.store_id = ?
    ORDER BY oi.order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $store_id
    ]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 整理商品資料
    foreach ($items as &$item) {

        $item["order_item_id"] =
            (int)$item["order_item_id"];

        $item["product_id"] =
            (int)$item["product_id"];

        if ($item["spec_id"] !== null) {
            $item["spec_id"] =
                (int)$item["spec_id"];
        }

        $item["quantity"] =
            (int)$item["quantity"];

        $item["price"] =
            (float)$item["price"];

        $item["subtotal"] =
            $item["quantity"] *
            $item["price"];
    }

    unset($item);

    // 整理退款資料
    $refund["refund_id"] =
        (int)$refund["refund_id"];

    $refund["order_id"] =
        (int)$refund["order_id"];

    $refund["store_id"] =
        (int)$refund["store_id"];

    $refund["total_amount"] =
        (float)$refund["total_amount"];

    // 整理回傳格式
    $result[] = [
        "refund_id" =>
            $refund["refund_id"],

        "order_id" =>
            $refund["order_id"],

        "store_id" =>
            $refund["store_id"],

        "payment" => [
            "paid_at" =>
                $refund["paid_at"]
        ],

        "total_amount" =>
            $refund["total_amount"],

        "items" =>
            $items,

        "refund_reason" =>
            $refund["refund_reason"],

        "refund_description" =>
            $refund["refund_description"],

        "refund_image_url" =>
            $refund["refund_image_url"],

        "refund_status" =>
            $refund["refund_status"],

        "admin_reply" =>
            $refund["admin_reply"],

        "requested_at" =>
            $refund["requested_at"],

        "processed_at" =>
            $refund["processed_at"]
    ];
}

echo json_encode([
    "refunds" => $result
], JSON_UNESCAPED_UNICODE);

?>