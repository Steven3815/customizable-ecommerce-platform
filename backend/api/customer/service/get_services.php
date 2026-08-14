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

$status = $_GET["status"] ?? "all";

if (
    $status !== "all" &&
    $status !== "pending" &&
    $status !== "resolved"
) {
    echo json_encode([
        "error" => "Invalid status"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
    o.delivery_status

FROM CUSTOMER_SERVICE cs

JOIN STORE s
    ON cs.store_id = s.store_id

LEFT JOIN ORDERS o
    ON cs.order_id = o.order_id
    AND cs.store_id = o.store_id

WHERE cs.customer_id = ?
";

$params = [
    $customer_id
];

if ($status === "pending") {

    $sql .= "
        AND cs.status = 'pending'
    ";

} elseif ($status === "resolved") {

    $sql .= "
        AND cs.status = 'resolved'
    ";
}

$sql .= "
ORDER BY cs.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];

foreach ($services as $service) {

    $result[] = [
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
}

echo json_encode([
    "status_filter" => $status,
    "count" => count($result),
    "services" => $result
], JSON_UNESCAPED_UNICODE);

?>