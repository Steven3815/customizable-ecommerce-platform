<?php

// Customer 取得購物車內容

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store ID
if (!isset($data["store_id"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Store ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = $data["store_id"];

if (
    !is_numeric($store_id) ||
    floor((float)$store_id) != (float)$store_id ||
    (int)$store_id <= 0
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid store ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$store_id = (int)$store_id;

// 排序
$sort_by = $data["sort_by"] ?? "created_at";
$sort_order = $data["sort_order"] ?? "desc";

if (
    !in_array(
        $sort_by,
        ["price", "created_at"],
        true
    )
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid sort field"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    !in_array(
        $sort_order,
        ["asc", "desc"],
        true
    )
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid sort order"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($sort_by === "price") {

    $order_by = "
        CASE
            WHEN p.has_spec = 1
            THEN ps.price
            ELSE p.price
        END
        $sort_order
    ";

} else {

    $order_by = "
        ci.created_at
        $sort_order
    ";
}

// Store
$sql = "
SELECT
    store_id,
    status
FROM STORE
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    http_response_code(404);
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($store["status"] !== "active") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is inactive"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Store Setting
$sql = "
SELECT
    store_mode
FROM STORE_SETTING
WHERE store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);

$store_setting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store_setting) {
    http_response_code(404);
    echo json_encode([
        "error" => "Store setting not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($store_setting["store_mode"] !== "shopping") {
    http_response_code(403);
    echo json_encode([
        "error" => "Store is currently in showcase mode"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得購物車
$sql = "
SELECT
    cart_id
FROM CART
WHERE customer_id = ?
AND store_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $customer_id,
    $store_id
]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    echo json_encode([
        "message" => "Cart is empty",
        "customer_id" => $customer_id,
        "store_id" => $store_id,
        "items" => [],
        "total_amount" => 0
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$cart_id = (int)$cart["cart_id"];

// 取得購物車商品
$sql = "
SELECT
    ci.cart_item_id,
    ci.created_at AS cart_item_created_at,

    p.product_id,
    p.product_name,
    p.description,

    ci.quantity,

    CASE
        WHEN p.has_spec = 1
        THEN ps.price
        ELSE p.price
    END AS price,

    (
        ci.quantity *
        CASE
            WHEN p.has_spec = 1
            THEN ps.price
            ELSE p.price
        END
    ) AS subtotal,

    ps.spec_id,
    ps.spec_name,

    (
        SELECT pi2.image_url
        FROM PRODUCT_IMAGE pi2
        WHERE pi2.product_id = p.product_id
        AND pi2.store_id = p.store_id
        ORDER BY
            pi2.sort_order ASC,
            pi2.image_id ASC
        LIMIT 1
    ) AS image_url

FROM CART_ITEM ci

INNER JOIN PRODUCT p
    ON ci.product_id = p.product_id
    AND ci.store_id = p.store_id

INNER JOIN CATEGORY c
    ON p.category_id = c.category_id
    AND p.store_id = c.store_id
    AND c.status = 'active'

LEFT JOIN PRODUCT_SPEC ps
    ON ci.spec_id = ps.spec_id
    AND ci.product_id = ps.product_id
    AND ci.store_id = ps.store_id

WHERE ci.cart_id = ?
AND ci.store_id = ?
AND p.status = 'active'

AND (
    p.has_spec = 0
    OR (
        ps.spec_id IS NOT NULL
        AND ps.status = 'active'
    )
)

ORDER BY
    $order_by,
    ci.cart_item_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $cart_id,
    $store_id
]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 計算總金額
$total_amount = 0;

foreach ($items as &$item) {

    $item["cart_item_id"] = (int)$item["cart_item_id"];

    $item["product_id"] = (int)$item["product_id"];

    $item["quantity"] = (int)$item["quantity"];

    $item["spec_id"] =
        $item["spec_id"] !== null
            ? (int)$item["spec_id"]
            : null;

    $item["price"] =
        $item["price"] !== null
            ? (float)$item["price"]
            : null;

    $item["subtotal"] =
        $item["subtotal"] !== null
            ? (float)$item["subtotal"]
            : null;

    if ($item["subtotal"] !== null) {
        $total_amount += $item["subtotal"];
    }
}

unset($item);

// 回傳
echo json_encode([
    "message" => "Cart retrieved successfully",
    "customer_id" => $customer_id,
    "cart_id" => $cart_id,
    "store_id" => $store_id,
    "sort_by" => $sort_by,
    "sort_order" => $sort_order,
    "items" => $items,
    "total_amount" => $total_amount
], JSON_UNESCAPED_UNICODE);

?>