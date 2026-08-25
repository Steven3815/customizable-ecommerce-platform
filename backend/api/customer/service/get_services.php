<?php

// Customer 取得指定 Store 的客服案件列表

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/customer_auth.php";

// 取得 Store ID
if (!isset($_GET["store_id"])) {
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $_GET["store_id"];

// 檢查 Store ID
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

// 檢查 Store
$sql = "
SELECT
    s.store_id,
    s.store_name,
    s.status,
    ss.store_mode
FROM STORE s

INNER JOIN STORE_SETTING ss
    ON s.store_id = ss.store_id

WHERE s.store_id = ?
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

// Store 必須為 active
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 展示模式不可使用客服
if ($store["store_mode"] !== "shopping") {
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得狀態篩選
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

// 取得客服案件
$sql = "
SELECT
    service_id,
    problem_type,
    created_at,
    status
FROM CUSTOMER_SERVICE
WHERE customer_id = ?
AND store_id = ?
";

$params = [
    $customer_id,
    $store_id
];

// 狀態篩選
if ($status === "pending") {

    $sql .= "
    AND status = 'pending'
    ";

} elseif ($status === "resolved") {

    $sql .= "
    AND status = 'resolved'
    ";
}

// 排序
$sql .= "
ORDER BY created_at DESC
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
        "problem_type" => $service["problem_type"],
        "created_at" => $service["created_at"],
        "status" => $service["status"]
    ];
}

// 回傳
echo json_encode([
    "store" => [
        "store_id" => (int)$store["store_id"],
        "store_name" => $store["store_name"]
    ],
    "status_filter" => $status,
    "count" => count($result),
    "services" => $result
], JSON_UNESCAPED_UNICODE);

?>