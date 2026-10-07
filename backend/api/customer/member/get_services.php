<?php

// Customer 會員中心取得所有 Store 的客服案件列表

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得狀態篩選
$status = $_GET["status"] ?? "all";

if (
    $status !== "all" &&
    $status !== "pending" &&
    $status !== "resolved"
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得客服案件
$sql = "
SELECT
    cs.service_id,
    cs.store_id,
    s.store_name,
    cs.problem_type,
    cs.created_at,
    cs.status

FROM CUSTOMER_SERVICE cs

INNER JOIN STORE s
    ON cs.store_id = s.store_id

WHERE cs.customer_id = ?
AND cs.status != 'cancelled'
";

$params = [
    $customer_id
];

// 狀態篩選
if ($status !== "all") {

    $sql .= "
    AND cs.status = ?
    ";

    $params[] = $status;
}

// 排序
$sql .= "
ORDER BY cs.created_at DESC
";

// 執行
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理資料
$result = [];

foreach ($services as $service) {

    $result[] = [
        "service_id" => (int)$service["service_id"],

        "store" => [
            "store_id" => (int)$service["store_id"],
            "store_name" => $service["store_name"]
        ],

        "problem_type" => $service["problem_type"],
        "created_at" => $service["created_at"],
        "status" => $service["status"]
    ];
}

// 回傳
echo json_encode([
    "message" => "Customer service cases retrieved successfully",
    "customer_id" => $customer_id,
    "status_filter" => $status,
    "count" => count($result),
    "services" => $result
], JSON_UNESCAPED_UNICODE);

?>