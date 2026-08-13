<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";

session_start();

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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON format"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $pdo->beginTransaction();

    if (isset($data["store_status"])) {
        $store_status = $data["store_status"];

        if (
            $store_status !== "open" &&
            $store_status !== "closed"
        ) {
            throw new Exception("Invalid store status");
        }

        $sql = "
        UPDATE STORE_SETTING
        SET store_status = ?
        WHERE store_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $store_status,
            $store_id
        ]);
    }

    if (isset($data["store_mode"])) {
        $store_mode = $data["store_mode"];

        if (
            $store_mode !== "shopping" &&
            $store_mode !== "showcase"
        ) {
            throw new Exception("Invalid store mode");
        }

        $sql = "
        UPDATE STORE_SETTING
        SET store_mode = ?
        WHERE store_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $store_mode,
            $store_id
        ]);
    }

    if (isset($data["refund"])) {
        if (!is_array($data["refund"])) {
            throw new Exception("Invalid refund settings");
        }

        if (isset($data["refund"]["enabled"])) {
            $refund_enable =
                $data["refund"]["enabled"];

            if (!is_bool($refund_enable)) {
                throw new Exception(
                    "Invalid refund enabled"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET refund_enable = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $refund_enable ? 1 : 0,
                $store_id
            ]);
        }

        if (isset($data["refund"]["days_limit"])) {
            $days_limit =
                $data["refund"]["days_limit"];

            if (
                !is_numeric($days_limit) ||
                floor((float)$days_limit)
                    != (float)$days_limit ||
                (int)$days_limit < 0
            ) {
                throw new Exception(
                    "Invalid refund days limit"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET refund_days_limit = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                (int)$days_limit,
                $store_id
            ]);
        }
    }

    if (isset($data["shipping"])) {
        if (!is_array($data["shipping"])) {
            throw new Exception(
                "Invalid shipping settings"
            );
        }

        if (isset($data["shipping"]["shipping_days"])) {
            $shipping_days =
                $data["shipping"]["shipping_days"];

            if (
                !is_numeric($shipping_days) ||
                floor((float)$shipping_days)
                    != (float)$shipping_days ||
                (int)$shipping_days < 0
            ) {
                throw new Exception(
                    "Invalid shipping days"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET shipping_days = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                (int)$shipping_days,
                $store_id
            ]);
        }

        if (isset($data["shipping"]["delivery_days"])) {
            $delivery_days =
                $data["shipping"]["delivery_days"];

            if (
                !is_numeric($delivery_days) ||
                floor((float)$delivery_days)
                    != (float)$delivery_days ||
                (int)$delivery_days < 0
            ) {
                throw new Exception(
                    "Invalid delivery days"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET delivery_days = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                (int)$delivery_days,
                $store_id
            ]);
        }
    }

    if (isset($data["stock_alert"])) {
        if (!is_array($data["stock_alert"])) {
            throw new Exception(
                "Invalid stock alert settings"
            );
        }

        if (isset($data["stock_alert"]["enabled"])) {
            $stock_alert_enable =
                $data["stock_alert"]["enabled"];

            if (!is_bool($stock_alert_enable)) {
                throw new Exception(
                    "Invalid stock alert enabled"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET stock_alert_enable = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $stock_alert_enable ? 1 : 0,
                $store_id
            ]);
        }

        if (
            isset($data["stock_alert"]["threshold"])
        ) {
            $threshold =
                $data["stock_alert"]["threshold"];

            if (
                $threshold !== null &&
                (
                    !is_numeric($threshold) ||
                    floor((float)$threshold)
                        != (float)$threshold ||
                    (int)$threshold < 0
                )
            ) {
                throw new Exception(
                    "Invalid stock alert threshold"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET stock_alert_threshold = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $threshold === null
                    ? null
                    : (int)$threshold,
                $store_id
            ]);
        }
    }

    if (isset($data["customer_service"])) {
        if (!is_array($data["customer_service"])) {
            throw new Exception(
                "Invalid customer service settings"
            );
        }

        if (
            isset(
                $data["customer_service"]["enabled"]
            )
        ) {
            $customer_service_enable =
                $data["customer_service"]["enabled"];

            if (!is_bool($customer_service_enable)) {
                throw new Exception(
                    "Invalid customer service enabled"
                );
            }

            $sql = "
            UPDATE STORE_SETTING
            SET customer_service_enable = ?
            WHERE store_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $customer_service_enable ? 1 : 0,
                $store_id
            ]);
        }
    }

    if (isset($data["payment_methods"])) {
        if (!is_array($data["payment_methods"])) {
            throw new Exception(
                "Invalid payment methods"
            );
        }

        foreach ($data["payment_methods"] as $payment) {
            if (!is_array($payment)) {
                throw new Exception(
                    "Invalid payment method data"
                );
            }

            if (
                !isset($payment["store_payment_id"]) ||
                !isset($payment["enabled"])
            ) {
                throw new Exception(
                    "Payment method ID and enabled are required"
                );
            }

            $store_payment_id =
                $payment["store_payment_id"];

            $enabled =
                $payment["enabled"];

            if (
                !is_numeric($store_payment_id) ||
                floor((float)$store_payment_id)
                    != (float)$store_payment_id ||
                (int)$store_payment_id < 1 ||
                (int)$store_payment_id > 5
            ) {
                throw new Exception(
                    "Invalid payment method ID"
                );
            }

            $store_payment_id =
                (int)$store_payment_id;

            if (!is_bool($enabled)) {
                throw new Exception(
                    "Invalid payment method enabled"
                );
            }

            $payment_map = [
                1 => "credit_card",
                2 => "atm",
                3 => "post_office",
                4 => "cash_on_delivery",
                5 => "in_store"
            ];

            $expected_payment_method =
                $payment_map[$store_payment_id];

            $sql = "
            SELECT
                store_payment_id,
                payment_method,
                status
            FROM STORE_PAYMENT_METHOD
            WHERE store_id = ?
            AND store_payment_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $store_id,
                $store_payment_id
            ]);

            $payment_method =
                $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment_method) {
                throw new Exception(
                    "Payment method not found"
                );
            }

            if (
                $payment_method["payment_method"]
                !== $expected_payment_method
            ) {
                throw new Exception(
                    "Payment method configuration is invalid"
                );
            }

            $sql = "
            UPDATE STORE_PAYMENT_METHOD
            SET status = ?
            WHERE store_id = ?
            AND store_payment_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $enabled
                    ? "active"
                    : "inactive",
                $store_id,
                $store_payment_id
            ]);

            if ($store_payment_id === 2) {
                $has_bank_data =
                    isset($payment["bank_name"]) ||
                    isset($payment["bank_number"]);

                if ($has_bank_data) {
                    $bank_name =
                        trim(
                            $payment["bank_name"] ?? ""
                        );

                    $bank_number =
                        trim(
                            $payment["bank_number"] ?? ""
                        );

                    if (
                        $bank_name === "" ||
                        $bank_number === ""
                    ) {
                        throw new Exception(
                            "Bank name and bank number are required"
                        );
                    }

                    $sql = "
                    SELECT account_id
                    FROM STORE_PAYMENT_ACCOUNT
                    WHERE store_id = ?
                    AND store_payment_id = ?
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $store_id,
                        $store_payment_id
                    ]);

                    $account =
                        $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($account) {
                        $sql = "
                        UPDATE STORE_PAYMENT_ACCOUNT
                        SET
                            bank_name = ?,
                            bank_number = ?
                        WHERE account_id = ?
                        AND store_id = ?
                        ";

                        $stmt = $pdo->prepare($sql);

                        $stmt->execute([
                            $bank_name,
                            $bank_number,
                            (int)$account["account_id"],
                            $store_id
                        ]);
                    } else {
                        $sql = "
                        INSERT INTO STORE_PAYMENT_ACCOUNT
                        (
                            store_id,
                            store_payment_id,
                            bank_name,
                            bank_number
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?
                        )
                        ";

                        $stmt = $pdo->prepare($sql);

                        $stmt->execute([
                            $store_id,
                            $store_payment_id,
                            $bank_name,
                            $bank_number
                        ]);
                    }
                }
            }

            if ($store_payment_id === 3) {
                if (
                    isset(
                        $payment["post_office_number"]
                    )
                ) {
                    $post_office_number =
                        trim(
                            $payment["post_office_number"]
                        );

                    if ($post_office_number === "") {
                        throw new Exception(
                            "Post office number is required"
                        );
                    }

                    $sql = "
                    SELECT account_id
                    FROM STORE_PAYMENT_ACCOUNT
                    WHERE store_id = ?
                    AND store_payment_id = ?
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $store_id,
                        $store_payment_id
                    ]);

                    $account =
                        $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($account) {
                        $sql = "
                        UPDATE STORE_PAYMENT_ACCOUNT
                        SET
                            post_office_number = ?
                        WHERE account_id = ?
                        AND store_id = ?
                        ";

                        $stmt = $pdo->prepare($sql);

                        $stmt->execute([
                            $post_office_number,
                            (int)$account["account_id"],
                            $store_id
                        ]);
                    } else {
                        $sql = "
                        INSERT INTO STORE_PAYMENT_ACCOUNT
                        (
                            store_id,
                            store_payment_id,
                            post_office_number
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?
                        )
                        ";

                        $stmt = $pdo->prepare($sql);

                        $stmt->execute([
                            $store_id,
                            $store_payment_id,
                            $post_office_number
                        ]);
                    }
                }
            }
        }
    }

if (isset($data["delivery_methods"])) {

    if (!is_array($data["delivery_methods"])) {
        throw new Exception(
            "Invalid delivery methods"
        );
    }

    // 固定配送方式 ID 對應
    // 1 = home_delivery
    // 2 = convenience_store
    // 3 = store_pickup

    $delivery_map = [
        1 => "home_delivery",
        2 => "convenience_store",
        3 => "store_pickup"
    ];

    foreach (
        $data["delivery_methods"]
        as $delivery
    ) {

        if (!is_array($delivery)) {
            throw new Exception(
                "Invalid delivery method data"
            );
        }

        // 檢查必要欄位
        if (
            !isset($delivery["store_delivery_id"]) ||
            !isset($delivery["enabled"])
        ) {
            throw new Exception(
                "Delivery method ID and enabled are required"
            );
        }

        $store_delivery_id =
            $delivery["store_delivery_id"];

        $enabled =
            $delivery["enabled"];

        // 檢查 Delivery ID
        if (
            !is_numeric($store_delivery_id) ||
            floor((float)$store_delivery_id)
                != (float)$store_delivery_id ||
            (int)$store_delivery_id < 1 ||
            (int)$store_delivery_id > 3
        ) {
            throw new Exception(
                "Invalid delivery method ID"
            );
        }

        $store_delivery_id =
            (int)$store_delivery_id;

        // 檢查 enabled
        if (!is_bool($enabled)) {
            throw new Exception(
                "Invalid delivery method enabled"
            );
        }

        // 取得預期配送方式
        $expected_delivery_method =
            $delivery_map[$store_delivery_id];

        // 檢查資料庫設定
        $sql = "
        SELECT
            store_delivery_id,
            delivery_method,
            status
        FROM STORE_DELIVERY_METHOD
        WHERE store_id = ?
        AND store_delivery_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $store_id,
            $store_delivery_id
        ]);

        $delivery_method =
            $stmt->fetch(PDO::FETCH_ASSOC);

        // Delivery 不存在
        if (!$delivery_method) {
            throw new Exception(
                "Delivery method not found"
            );
        }

        // 檢查 ID 與名稱是否一致
        if (
            $delivery_method["delivery_method"]
            !== $expected_delivery_method
        ) {
            throw new Exception(
                "Delivery method configuration is invalid"
            );
        }

        // 更新啟用狀態
        $sql = "
        UPDATE STORE_DELIVERY_METHOD
        SET
            status = ?
        WHERE store_id = ?
        AND store_delivery_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $enabled
                ? "active"
                : "inactive",
            $store_id,
            $store_delivery_id
        ]);
    }
}

    $pdo->commit();

    echo json_encode([
        "message" =>
            "Store settings updated successfully"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>