<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

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

if ($customer_id <= 0) {
    echo json_encode([
        "error" => "Invalid customer ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_GET["store_id"])) {
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_GET["service_id"])) {
    echo json_encode([
        "error" => "Service ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$store_id = $_GET["store_id"];
$service_id = $_GET["service_id"];

if (
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$store_id = (int)$store_id;

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

    s.store_name,

    o.total_amount,
    o.delivery_method,
    o.delivery_status,
    o.estimated_ship_date,
    o.estimated_arrival_date

FROM CUSTOMER_SERVICE cs

JOIN STORE s
    ON cs.store_id = s.store_id

LEFT JOIN ORDERS o
    ON cs.order_id = o.order_id
    AND cs.store_id = o.store_id

WHERE cs.service_id = ?
AND cs.customer_id = ?
AND cs.store_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $service_id,
    $customer_id,
    $store_id
]);

$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    echo json_encode([
        "error" => "Service not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = [
    "service_id" => (int)$service["service_id"],

    "store" => [
        "store_id" => (int)$service["store_id"],
        "store_name" => $service["store_name"]
    ],

    "order" => $service["order_id"] !== null
        ? [
            "order_id" => (int)$service["order_id"],

            "total_amount" => $service["total_amount"] !== null
                ? (float)$service["total_amount"]
                : null,

            "delivery_method" => $service["delivery_method"],

            "delivery_status" => $service["delivery_status"],

            "estimated_ship_date" =>
                $service["estimated_ship_date"],

            "estimated_arrival_date" =>
                $service["estimated_arrival_date"]
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

echo json_encode([
    "service" => $result
], JSON_UNESCAPED_UNICODE);

?>