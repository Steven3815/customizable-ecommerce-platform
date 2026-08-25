<?php

// Store 取得單一客服案件

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";

// 檢查 Service ID
if (!isset($_GET["service_id"])) {
    echo json_encode([
        "error" => "Service ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$service_id = $_GET["service_id"];

if (
    !is_numeric($service_id) ||
    floor((float)$service_id) != (float)$service_id ||
    (int)$service_id <= 0
) {
    echo json_encode([
        "error" => "Invalid service ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$service_id = (int)$service_id;

// 取得客服案件
$sql = "
SELECT
    cs.service_id,
    cs.customer_id,
    cs.store_id,
    cs.order_id,

    cs.problem_type,
    cs.description,
    cs.image_url,

    cs.status,
    cs.admin_reply,

    cs.created_at,
    cs.updated_at,

    c.name AS customer_name,
    c.email AS customer_email,
    o.order_number,
    o.total_amount,
    o.delivery_method,
    o.delivery_status

FROM CUSTOMER_SERVICE cs

JOIN CUSTOMER c
    ON cs.customer_id = c.customer_id

LEFT JOIN ORDERS o
    ON cs.order_id = o.order_id
    AND cs.store_id = o.store_id

WHERE cs.service_id = ?
AND cs.store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $service_id,
    $store_id
]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

// 客服案件不存在
if (!$service) {
    echo json_encode([
        "error" => "Service not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 整理資料
$result = [
    "service_id" => (int)$service["service_id"],

    "customer" => [
        "customer_id" => (int)$service["customer_id"],
        "name" => $service["customer_name"],
        "email" => $service["customer_email"]
    ],

    "order" => $service["order_id"] !== null
        ? [
            "order_id" => (int)$service["order_id"],
            "order_number" => $service["order_number"],
            "total_amount" => $service["total_amount"] !== null
                ? (float)$service["total_amount"]
                : null,
            "delivery_method" => $service["delivery_method"],
            "delivery_status" => $service["delivery_status"]
        ]
        : null,

    "problem_type" => $service["problem_type"],
    "description" => $service["description"],
    "image_url" => $service["image_url"],
    "status" => $service["status"],
    "admin_reply" => $service["admin_reply"],
    "created_at" => $service["created_at"],
    "updated_at" => $service["updated_at"]
];

// 回傳
echo json_encode([
    "service" => $result
], JSON_UNESCAPED_UNICODE);

?>