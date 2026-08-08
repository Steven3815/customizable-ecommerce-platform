<?php

// 取得退款列表
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

// 檢查 customer_id
if (!isset($_GET["customer_id"])) {

    echo json_encode([
        "error" => "Customer ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$customer_id = $_GET["customer_id"];

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

// 取得退款資料
$sql = "
SELECT
    r.refund_id,
    r.order_id,
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
LEFT JOIN PAYMENT p
ON r.order_id = p.order_id
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
        "message" => "No refunds found",
        "refunds" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理每一筆退款
$result = [];

foreach ($refunds as $refund) {
    $order_id = $refund["order_id"];

    // 取得商品明細
    $sql = "
    SELECT
        order_item_id,
        product_id,
        spec_id,
        product_name,
        spec_name,
        quantity,
        price
    FROM ORDER_ITEM
    WHERE order_id = ?
    ORDER BY order_item_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);

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

    $refund["total_amount"] =
        (float)$refund["total_amount"];

    // 整理回傳格式
    $result[] = [

        "refund_id" =>
            $refund["refund_id"],

        "order_id" =>
            $refund["order_id"],

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