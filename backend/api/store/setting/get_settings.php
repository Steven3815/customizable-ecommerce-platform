<?php

// Store 取得商店設定

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

try {

    // 取得商店設定
    $sql = "
    SELECT
        store_id,
        store_status,
        store_mode,
        refund_enable,
        refund_days_limit,
        shipping_days,
        delivery_days,
        stock_alert_enable,
        stock_alert_threshold,
        spec_stock_alert_threshold,
        customer_service_enable
    FROM STORE_SETTING
    WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $store_settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$store_settings) {
        echo json_encode([
            "error" => "Store settings not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 資料型別整理
    $store_settings["store_id"] = (int)$store_settings["store_id"];
    $store_settings["refund_enable"] = (bool)$store_settings["refund_enable"];
    $store_settings["refund_days_limit"] = (int)$store_settings["refund_days_limit"];
    $store_settings["shipping_days"] = (int)$store_settings["shipping_days"];
    $store_settings["delivery_days"] = (int)$store_settings["delivery_days"];
    $store_settings["stock_alert_enable"] = (bool)$store_settings["stock_alert_enable"];

    if ($store_settings["stock_alert_threshold"] !== null) {
        $store_settings["stock_alert_threshold"] = (int)$store_settings["stock_alert_threshold"];
    }

    if ($store_settings["spec_stock_alert_threshold"] !== null) {
        $store_settings["spec_stock_alert_threshold"] = (int)$store_settings["spec_stock_alert_threshold"];
    }

    $store_settings["customer_service_enable"] = (bool)$store_settings["customer_service_enable"];

    // 取得付款方式
    $sql = "
    SELECT
        spm.store_payment_id,
        spm.payment_method,
        spm.status,

        spa.account_id,
        spa.bank_name,
        spa.bank_number,
        spa.post_office_number

    FROM STORE_PAYMENT_METHOD spm

    LEFT JOIN STORE_PAYMENT_ACCOUNT spa
        ON spm.store_id = spa.store_id
        AND spm.store_payment_id = spa.store_payment_id

    WHERE spm.store_id = ?

    ORDER BY spm.store_payment_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $payment_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $payment_methods = [];

    foreach ($payment_rows as $payment) {
        $store_payment_id = (int)$payment["store_payment_id"];
        $payment_method = $payment["payment_method"];

        $item = [
            "store_payment_id" => $store_payment_id,
            "payment_method" => $payment_method,
            "enabled" => $payment["status"] === "active"
        ];

        // ATM
        if ($payment_method === "atm") {
            $item["account"] = [
                "account_id" => $payment["account_id"] !== null
                    ? (int)$payment["account_id"]
                    : null,
                "bank_name" => $payment["bank_name"],
                "bank_number" => $payment["bank_number"]
            ];
        }

        // 郵局轉帳
        if ($payment_method === "post_office") {
            $item["account"] = [
                "account_id" => $payment["account_id"] !== null
                    ? (int)$payment["account_id"]
                    : null,
                "post_office_number" => $payment["post_office_number"]
            ];
        }

        $payment_methods[] = $item;
    }

    // 取得配送方式
    $sql = "
    SELECT
        store_delivery_id,
        delivery_method,
        status

    FROM STORE_DELIVERY_METHOD

    WHERE store_id = ?

    ORDER BY store_delivery_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    $delivery_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $delivery_methods = [];

    foreach ($delivery_rows as $delivery) {

        $delivery_methods[] = [
            "store_delivery_id" => (int)$delivery["store_delivery_id"],
            "delivery_method" => $delivery["delivery_method"],
            "enabled" => $delivery["status"] === "active"
        ];
    }

    // 回傳
    echo json_encode([
        "store_settings" => $store_settings,
        "payment_methods" => $payment_methods,
        "delivery_methods" => $delivery_methods
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    echo json_encode([
        "error" => "Failed to get store settings"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>