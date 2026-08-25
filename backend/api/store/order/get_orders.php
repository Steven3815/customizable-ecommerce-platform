<?php

// Store 取得訂單列表
header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";

// 檢查 Store 是否啟用
if ($store["status"] !== "active") {
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得搜尋、篩選、排序條件
$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "all";
$refund_status = $_GET["refund_status"] ?? "all";
$payment_confirm_status = $_GET["payment_confirm_status"] ?? "all";
$sort = $_GET["sort"] ?? "newest";

// 分頁
$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;

if ($page < 1) {
    $page = 1;
}

$limit = 50;

$offset = ($page - 1) * $limit;

// 建立 WHERE
$where = "
WHERE o.store_id = ?
";

$params = [$store_id];

// 搜尋
if ($search !== "") {

    $where .= "
        AND (
            o.order_number LIKE ?
            OR c.name LIKE ?
            OR c.phone = ?
        )
    ";

    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search;
}

// 配送狀態篩選
if ($status === "pending") {

    $where .= "
        AND o.delivery_status = 'pending'
    ";

} elseif ($status === "shipping") {

    $where .= "
        AND o.delivery_status = 'shipping'
    ";

} elseif ($status === "completed") {

    $where .= "
        AND o.delivery_status = 'completed'
    ";

} elseif ($status !== "all") {

    echo json_encode([
        "error" => "Invalid delivery status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 退款狀態篩選
if ($refund_status === "none") {

    $where .= "
        AND NOT EXISTS (
            SELECT 1
            FROM REFUND r_none
            WHERE r_none.order_id = o.order_id
            AND r_none.store_id = o.store_id
        )
    ";

} elseif ($refund_status === "pending") {

    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r_pending
            WHERE r_pending.order_id = o.order_id
            AND r_pending.store_id = o.store_id
            AND r_pending.refund_status = 'pending'
        )
    ";

} elseif ($refund_status === "approved") {

    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r_approved
            WHERE r_approved.order_id = o.order_id
            AND r_approved.store_id = o.store_id
            AND r_approved.refund_status = 'approved'
        )
    ";

} elseif ($refund_status === "rejected") {

    $where .= "
        AND EXISTS (
            SELECT 1
            FROM REFUND r_rejected
            WHERE r_rejected.order_id = o.order_id
            AND r_rejected.store_id = o.store_id
            AND r_rejected.refund_status = 'rejected'
        )
    ";

} elseif ($refund_status !== "all") {

    echo json_encode([
        "error" => "Invalid refund status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 付款確認狀態篩選
if ($payment_confirm_status === "waiting") {

    $where .= "
        AND p.payment_confirm_status = 'waiting'
    ";

} elseif ($payment_confirm_status === "confirmed") {

    $where .= "
        AND p.payment_confirm_status = 'confirmed'
    ";

} elseif ($payment_confirm_status === "rejected") {

    $where .= "
        AND p.payment_confirm_status = 'rejected'
    ";

} elseif ($payment_confirm_status !== "all") {

    echo json_encode([
        "error" => "Invalid payment confirmation status"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 排序
switch ($sort) {

    case "newest":
        $orderBy = "o.created_at DESC";
        break;

    case "oldest":
        $orderBy = "o.created_at ASC";
        break;

    case "price_high":
        $orderBy = "o.total_amount DESC";
        break;

    case "price_low":
        $orderBy = "o.total_amount ASC";
        break;

    default:

        echo json_encode([
            "error" => "Invalid sort"
        ], JSON_UNESCAPED_UNICODE);

        exit;
}

// 計算訂單總數
$countSql = "
SELECT COUNT(*)
FROM ORDERS o
JOIN CUSTOMER c
    ON o.customer_id = c.customer_id
JOIN PAYMENT p
    ON o.order_id = p.order_id
    AND o.store_id = p.store_id
$where
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$total_pages =
    $total > 0
        ? (int)ceil($total / $limit)
        : 0;

// 取得訂單
$sql = "
SELECT
    o.order_id,
    o.order_number,
    o.store_id,
    o.created_at,
    o.customer_id,
    c.name AS customer_name,
    c.phone,
    o.total_amount,
    o.delivery_status,
    p.payment_confirm_status
FROM ORDERS o
JOIN CUSTOMER c
    ON o.customer_id = c.customer_id
JOIN PAYMENT p
    ON o.order_id = p.order_id
    AND o.store_id = p.store_id
$where
ORDER BY $orderBy
LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);

$param_index = 1;

foreach ($params as $param) {

    if (is_int($param)) {

        $stmt->bindValue(
            $param_index,
            $param,
            PDO::PARAM_INT
        );

    } else {

        $stmt->bindValue(
            $param_index,
            $param,
            PDO::PARAM_STR
        );
    }

    $param_index++;
}

$stmt->bindValue(
    $param_index,
    $limit,
    PDO::PARAM_INT
);

$param_index++;

$stmt->bindValue(
    $param_index,
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 沒有訂單
if (!$orders) {

    echo json_encode([
        "store_id" => $store_id,
        "store_name" => $store["store_name"],
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "total_pages" => $total_pages,
        "message" => "No orders found",
        "orders" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 整理訂單
$result = [];

foreach ($orders as $order) {

    $order_id = (int)$order["order_id"];

    // 取得最新退款狀態
    $sql = "
    SELECT
        refund_status
    FROM REFUND
    WHERE order_id = ?
    AND store_id = ?
    ORDER BY requested_at DESC
    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $order_id,
        $store_id
    ]);
    $refund = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$refund) {

        $current_refund_status = "none";
    } else {

        $current_refund_status = $refund["refund_status"];
    }

    // 整理回傳資料
    $result[] = [
    "order_id" => $order_id,
    "order_number" => $order["order_number"],
    "store_id" => (int)$order["store_id"],
    "created_at" => $order["created_at"],

    "customer" => [
        "customer_id" => (int)$order["customer_id"],
        "name" => $order["customer_name"],
        "phone" => $order["phone"]
    ],

    "total_amount" => (float)$order["total_amount"],
    "delivery_status" => $order["delivery_status"],
    "payment_confirm_status" => $order["payment_confirm_status"],
    "refund_status" => $current_refund_status
    ];
}
echo json_encode([
    "store_id" => $store_id,
    "store_name" => $store["store_name"],
    "page" => $page,
    "limit" => $limit,
    "total" => $total,
    "total_pages" => $total_pages,
    "orders" => $result
], JSON_UNESCAPED_UNICODE);

?>