<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/store_auth.php";

if (!isset($_GET["customer_id"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Customer ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = $_GET["customer_id"];

if (
    !is_numeric($customer_id) ||
    floor((float)$customer_id) != (float)$customer_id ||
    (int)$customer_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid customer ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = (int)$customer_id;

$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;

if ($page < 1) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid page"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sort = $_GET["sort"] ?? "created_at_desc";

$allowed_sort = [
    "created_at_asc",
    "created_at_desc",
    "amount_asc",
    "amount_desc"
];

if (!in_array($sort, $allowed_sort, true)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid sort option"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$limit = 30;
$offset = ($page - 1) * $limit;
$order_by = match ($sort) {
    "created_at_asc" => "o.created_at ASC, o.order_id ASC",
    "created_at_desc"  => "o.created_at DESC, o.order_id DESC",
    "amount_asc" => "o.total_amount ASC, o.order_id ASC",
    "amount_desc" => "o.total_amount DESC, o.order_id DESC"
};

// 取得客戶基本資料
$sql = "
SELECT
    c.customer_id,
    c.name,
    c.phone,
    c.email,
    c.created_at,

    COUNT(o.order_id) AS order_count,

    COALESCE(
        SUM(o.total_amount),
        0
    ) AS total_spending

FROM CUSTOMER c

JOIN ORDERS o
    ON c.customer_id = o.customer_id
    AND o.store_id = ?

WHERE c.customer_id = ?

GROUP BY
    c.customer_id,
    c.name,
    c.phone,
    c.email,
    c.created_at
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $store_id,
    $customer_id
]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    echo json_encode([
        "error" => "Customer not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

//計算購買記錄總筆數 
$count_sql = "
SELECT COUNT(*)

FROM ORDERS

WHERE customer_id = ?
AND store_id = ?
";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute([
    $customer_id,
    $store_id
]);
$total_orders = (int)$count_stmt->fetchColumn();

$total_pages = $total_orders > 0
    ? (int)ceil($total_orders / $limit)
    : 0;

///取得購買記錄 
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.created_at,
    o.total_amount,
    o.delivery_status,

    r.refund_status

FROM ORDERS o

LEFT JOIN REFUND r
    ON o.order_id = r.order_id
    AND r.store_id = o.store_id

WHERE o.customer_id = ?
AND o.store_id = ?

ORDER BY
    $order_by

LIMIT ?
OFFSET ?
";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(
    1,
    $customer_id,
    PDO::PARAM_INT
);

$stmt->bindValue(
    2,
    $store_id,
    PDO::PARAM_INT
);

$stmt->bindValue(
    3,
    $limit,
    PDO::PARAM_INT
);

$stmt->bindValue(
    4,
    $offset,
    PDO::PARAM_INT
);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$purchase_records = [];

foreach ($orders as $order) {

    $purchase_records[] = [
        "order_id" => (int)$order["order_id"],
        "order_number" => $order["order_number"],
        "created_at" => $order["created_at"],
        "total_amount" => (float)$order["total_amount"],
        "delivery_status" => $order["delivery_status"],
        "refund_status" => $order["refund_status"]
    ];
}

//回傳
echo json_encode([
    "customer" => [
        "customer_id" => (int)$customer["customer_id"],
        "name" => $customer["name"],
        "phone" => $customer["phone"],
        "email" => $customer["email"],
        "order_count" => (int)$customer["order_count"],
        "total_spending" => (float)$customer["total_spending"],
        "created_at" => $customer["created_at"]
    ],
    "purchase_records" => [
        "page" => $page,
        "limit" => $limit,
        "total_orders" => $total_orders,
        "total_pages" => $total_pages,
        "sort" => $sort,
        "orders" => $purchase_records
    ]
], JSON_UNESCAPED_UNICODE);

?>