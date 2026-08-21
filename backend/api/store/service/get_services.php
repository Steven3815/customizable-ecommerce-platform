<?php

// Store 取得客服案件列表

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

// 取得頁數與狀態篩選
$page = $_GET["page"] ?? 1;
$status = $_GET["status"] ?? "all";

// 檢查頁數
if (
    !is_numeric($page) ||
    floor((float)$page) != (float)$page ||
    (int)$page <= 0
) {
    echo json_encode([
        "error" => "Invalid page"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$page = (int)$page;

// 檢查客服狀態
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

// 每頁 30 筆
$limit = 30;

// 計算資料起始位置
$offset = ($page - 1) * $limit;

// 取得符合條件的客服案件總數
$count_sql = "
SELECT COUNT(*)
FROM CUSTOMER_SERVICE
WHERE store_id = ?
";

$count_params = [
    $store_id
];

if ($status !== "all") {
    $count_sql .= "
        AND status = ?
    ";

    $count_params[] = $status;
}

$stmt = $pdo->prepare($count_sql);

$stmt->execute($count_params);

$total = (int)$stmt->fetchColumn();

// 計算總頁數
$total_pages = $total > 0
    ? (int)ceil($total / $limit)
    : 0;

// 取得客服案件列表
$sql = "
SELECT
    cs.service_id,
    cs.customer_id,
    cs.store_id,
    cs.order_id,
    o.order_number,
    cs.problem_type,
    cs.status,
    cs.created_at,

    c.name AS customer_name

FROM CUSTOMER_SERVICE cs

JOIN CUSTOMER c
    ON cs.customer_id = c.customer_id

LEFT JOIN ORDERS o
    ON cs.order_id = o.order_id
    AND cs.store_id = o.store_id

WHERE cs.store_id = ?
";

$params = [
    $store_id
];

// 套用狀態篩選
if ($status !== "all") {
    $sql .= "
        AND cs.status = ?
    ";

    $params[] = $status;
}

// 按建立時間由新到舊排列
$sql .= "
ORDER BY cs.created_at DESC
LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理回傳資料
$result = [];

foreach ($services as $service) {

    $result[] = [
        "service_id" => (int)$service["service_id"],

        "customer_id" => (int)$service["customer_id"],

        "customer_name" => $service["customer_name"],

        "order_id" => $service["order_id"] !== null
            ? (int)$service["order_id"]
            : null,
        "order_number" => $service["order_number"],
        
        "problem_type" => $service["problem_type"],

        "status" => $service["status"],

        "created_at" => $service["created_at"]
    ];
}

// 回傳
echo json_encode([
    "page" => $page,
    "limit" => $limit,
    "total" => $total,
    "total_pages" => $total_pages,
    "status_filter" => $status,
    "services" => $result
], JSON_UNESCAPED_UNICODE);

?>