<?php

// Store 取得單筆退款詳細資料
header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

// 檢查 Store Session
if (
    !isset($_SESSION["store_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "store"
) {
    echo json_encode([
        "error" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$_SESSION["store_id"];

// 檢查 Store ID
if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 Store 是否存在
$sql = "
SELECT
    store_id
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 refund_id
if (!isset($_GET["refund_id"])) {
    echo json_encode([
        "error" => "Refund ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$refund_id = (int)$_GET["refund_id"];

if ($refund_id <= 0) {
    echo json_encode([
        "error" => "Invalid refund ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得退款基本資料
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
    o.customer_id,
    o.total_amount,
    p.paid_at
FROM REFUND r
JOIN ORDERS o
ON r.order_id = o.order_id
LEFT JOIN PAYMENT p
ON r.order_id = p.order_id
WHERE r.refund_id = ?
AND EXISTS (
    SELECT 1
    FROM ORDER_ITEM oi
    JOIN PRODUCT pr
    ON oi.product_id = pr.product_id
    WHERE oi.order_id = r.order_id
    AND pr.store_id = ?
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $refund_id,
    $store_id
]);

$refund = $stmt->fetch(PDO::FETCH_ASSOC);

// 退款不存在
if (!$refund) {
    echo json_encode([
        "error" => "Refund not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理退款基本資料
$refund["refund_id"] =
    (int)$refund["refund_id"];

$refund["order_id"] =
    (int)$refund["order_id"];

$refund["customer_id"] =
    (int)$refund["customer_id"];

$refund["total_amount"] =
    (float)$refund["total_amount"];

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
JOIN PRODUCT pr
ON oi.product_id = pr.product_id
WHERE oi.order_id = ?
AND pr.store_id = ?
ORDER BY oi.order_item_id ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $refund["order_id"],
    $store_id
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理商品明細
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

// 回傳
echo json_encode([

    "store_id" =>
        $store_id,

    "refund" => [

        "refund_id" =>
            $refund["refund_id"],

        "order_id" =>
            $refund["order_id"],

        "customer_id" =>
            $refund["customer_id"],

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
    ]

], JSON_UNESCAPED_UNICODE);

?>