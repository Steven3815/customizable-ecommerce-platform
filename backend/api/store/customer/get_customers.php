<?php

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

// 分頁
$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;

if ($page < 1) {
    echo json_encode([
        "error" => "Invalid page"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$limit = 30;
$offset = ($page - 1) * $limit;

// 搜尋
$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

// 排序
$sort = $_GET["sort"] ?? "created_at_desc";

$allowed_sort = [
    "order_count_asc",
    "order_count_desc",
    "total_spending_asc",
    "total_spending_desc",
    "created_at_asc",
    "created_at_desc"
];

if (!in_array($sort, $allowed_sort, true)) {
    echo json_encode([
        "error" => "Invalid sort option"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 搜尋條件
$search_condition = "";

$count_params = [$store_id];

$data_params = [$store_id];

if ($search !== "") {

    $search_condition = "
        AND (
            c.name LIKE ?
            OR c.email LIKE ?
            OR c.phone LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $count_params[] = $search_value;
    $count_params[] = $search_value;
    $count_params[] = $search_value;

    $data_params[] = $search_value;
    $data_params[] = $search_value;
    $data_params[] = $search_value;
}

// 排序條件
switch ($sort) {

    case "order_count_asc":
        $order_by = "order_count ASC";
        break;

    case "order_count_desc":
        $order_by = "order_count DESC";
        break;

    case "total_spending_asc":
        $order_by = "total_spending ASC";
        break;

    case "total_spending_desc":
        $order_by = "total_spending DESC";
        break;

    case "created_at_asc":
        $order_by = "c.created_at ASC";
        break;

    case "created_at_desc":
        $order_by = "c.created_at DESC";
        break;

    default:
        echo json_encode([
            "error" => "Invalid sort option"
        ], JSON_UNESCAPED_UNICODE);

        exit;
}

// 計算符合條件的客戶數
$count_sql = "
SELECT
    COUNT(*)
FROM CUSTOMER c
WHERE EXISTS (
    SELECT 1
    FROM ORDERS o
    WHERE o.customer_id = c.customer_id
    AND o.store_id = ?
)
$search_condition
";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);

$total_customers =(int)$count_stmt->fetchColumn();

$total_pages = $total_customers > 0
    ? (int)ceil(
        $total_customers / $limit
    )
    : 0;

// 取得客戶資料
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

WHERE 1 = 1

$search_condition

GROUP BY
    c.customer_id,
    c.name,
    c.phone,
    c.email,
    c.created_at

ORDER BY
    $order_by

LIMIT $limit
OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($data_params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理資料
$result = [];

foreach ($customers as $customer) {
    $result[] = [
        "customer_id" => (int)$customer["customer_id"],
        "name" => $customer["name"],
        "phone" => $customer["phone"],
        "email" => $customer["email"],
        "order_count" => (int)$customer["order_count"],
        "total_spending" => (float)$customer["total_spending"],
        "created_at" => $customer["created_at"]
    ];
}

// 回傳
echo json_encode([
    "page" => $page,
    "limit" => $limit,
    "total_customers" => $total_customers,
    "total_pages" => $total_pages,
    "search" => $search,
    "sort" => $sort,
    "customers" => $result
], JSON_UNESCAPED_UNICODE);

?>