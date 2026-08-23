<?php

// Store 更新商品

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../../config/database.php";

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

// 取得 JSON
$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得 product_id
if (
    !isset($data["product_id"]) ||
    $data["product_id"] === ""
) {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = $data["product_id"];

if (
    !is_numeric($product_id) ||
    floor((float)$product_id)
    != (float)$product_id
) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_id = (int)$product_id;

if ($product_id <= 0) {
    echo json_encode([
        "error" => "Invalid product ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得商品名稱
if (
    !isset($data["product_name"])
) {
    echo json_encode([
        "error" => "Product name is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$product_name = trim($data["product_name"]);

if ($product_name === "") {
    echo json_encode([
        "error" => "Product name is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 商品名稱最多 200 字
if (mb_strlen($product_name) > 200) {
    echo json_encode([
        "error" => "Product name is too long"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $sql = "
        SELECT
            product_id,
            store_id,
            category_id,
            product_name,
            sort_order,
            status
        FROM PRODUCT
        WHERE product_id = ?
        AND store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {

        echo json_encode([
            "error" => "Product not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 更新商品名稱
    $sql = "
        UPDATE PRODUCT
        SET
            product_name = ?,
            updated_at = NOW()
        WHERE product_id = ?
        AND store_id = ?
        AND status = 'active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_name,
        $product_id,
        $store_id
    ]);

    // 取得更新後商品
    $sql = "
        SELECT
            product_id,
            store_id,
            category_id,
            product_name,
            sort_order,
            status,
            updated_at
        FROM PRODUCT
        WHERE product_id = ?
        AND store_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id,
        $store_id
    ]);
    $updated_product = $stmt->fetch(PDO::FETCH_ASSOC);

    // 整理資料
    $updated_product["product_id"] = (int)$updated_product["product_id"];
    $updated_product["store_id"] = (int)$updated_product["store_id"];
    $updated_product["category_id"] = (int)$updated_product["category_id"];
    $updated_product["sort_order"] =
        $updated_product["sort_order"] !== null
            ? (int)$updated_product["sort_order"]
            : null;

    // 回傳
    echo json_encode([
        "message" => "Product updated successfully",
        "product" => $updated_product
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>