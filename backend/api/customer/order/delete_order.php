<?php

// Customer 取消訂單
// Soft Delete：將 order_status 改為 cancelled

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/cors.php";
require_once "../../../middleware/customer_auth.php";

// 取得 JSON 資料
$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON data"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查必要欄位
if (
    !isset($data["order_number"]) ||
    !isset($data["store_id"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$order_number = trim($data["order_number"]);
$store_id = $data["store_id"];

// 檢查訂單編號
if ($order_number === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Order number is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查 store_id
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

$pdo->beginTransaction();

try {

    // 取得訂單
    // 確認訂單屬於目前登入會員與指定商店
    $sql = "
    SELECT
        order_id,
        order_number,
        customer_id,
        store_id,
        order_status

    FROM ORDERS

    WHERE order_number = ?
    AND customer_id = ?
    AND store_id = ?

    FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_number,
        $customer_id,
        $store_id
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception(
            "Order not found",
            404
        );
    }

    // 只有 pending 訂單可以取消
    if ($order["order_status"] !== "pending") {
        throw new Exception(
            "Only pending orders can be cancelled",
            409
        );
    }

    $order_id = (int)$order["order_id"];

    // 取得訂單商品
    $sql = "
    SELECT
        product_id,
        spec_id,
        quantity

    FROM ORDER_ITEM

    WHERE order_id = ?
    AND store_id = ?

    FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $store_id
    ]);

    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 回補商品庫存
    foreach ($order_items as $item) {

        $quantity = (int)$item["quantity"];

        if ($quantity <= 0) {
            throw new Exception(
                "Invalid order item quantity",
                400
            );
        }

        // 有規格商品
        if ($item["spec_id"] !== null) {

            $sql = "
            UPDATE PRODUCT_SPEC

            SET
                stock = stock + ?,
                updated_at = NOW()

            WHERE spec_id = ?
            AND product_id = ?
            AND store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $quantity,
                $item["spec_id"],
                $item["product_id"],
                $store_id
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "Failed to restore specification stock",
                    500
                );
            }

        } else {

            // 無規格商品
            $sql = "
            UPDATE PRODUCT

            SET
                stock = stock + ?,
                updated_at = NOW()

            WHERE product_id = ?
            AND store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $quantity,
                $item["product_id"],
                $store_id
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "Failed to restore product stock",
                    500
                );
            }
        }
    }

    // Soft Delete
    // 將訂單狀態改為 cancelled
    $sql = "
    UPDATE ORDERS

    SET
        order_status = 'cancelled'

    WHERE order_id = ?
    AND order_number = ?
    AND customer_id = ?
    AND store_id = ?
    AND order_status = 'pending'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_id,
        $order_number,
        $customer_id,
        $store_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Failed to cancel order",
            500
        );
    }

    // 完成交易
    $pdo->commit();

    echo json_encode([
        "message" => "Order cancelled successfully",
        "order_id" => $order_id,
        "order_number" => $order_number,
        "customer_id" => $customer_id,
        "store_id" => $store_id,
        "order_status" => "cancelled"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    // 發生錯誤時全部回滾
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status_code = $e->getCode();

    if (
        $status_code < 400 ||
        $status_code > 599
    ) {
        $status_code = 500;
    }

    http_response_code($status_code);

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>