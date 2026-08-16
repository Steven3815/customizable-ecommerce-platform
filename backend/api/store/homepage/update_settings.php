<?php

// Store 更新首頁管理設定

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

if ($store_id <= 0) {
    echo json_encode([
        "error" => "Invalid store ID"
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

try {

    $pdo->beginTransaction();

    // 更新首頁區塊設定
    if (
        !isset($data["intro_section_enable"]) ||
        !isset($data["banner_section_enable"])
    ) {
        throw new Exception(
            "Website setting is required"
        );
    }

    $intro_section_enable =
        (int)$data["intro_section_enable"];

    $banner_section_enable =
        (int)$data["banner_section_enable"];

    if (
        !in_array(
            $intro_section_enable,
            [0, 1],
            true
        ) ||
        !in_array(
            $banner_section_enable,
            [0, 1],
            true
        )
    ) {
        throw new Exception(
            "Invalid website setting"
        );
    }

    $sql = "
        UPDATE WEBSITE_SETTING
        SET
            intro_section_enable = ?,
            banner_section_enable = ?
        WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $intro_section_enable,
        $banner_section_enable,
        $store_id
    ]);

    if ($stmt->rowCount() === 0) {

        $check = $pdo->prepare("
            SELECT setting_id
            FROM WEBSITE_SETTING
            WHERE store_id = ?
        ");

        $check->execute([
            $store_id
        ]);

        if (!$check->fetch()) {
            throw new Exception(
                "Website setting not found"
            );
        }
    }

    // 更新首頁商品排列設定
    if (!isset($data["display_limit"])) {
        throw new Exception(
            "Display limit is required"
        );
    }

    $display_limit =
        (int)$data["display_limit"];

    if (
        !in_array(
            $display_limit,
            [4, 5, 6],
            true
        )
    ) {
        throw new Exception(
            "Display limit must be 4, 5, or 6"
        );
    }

    $sql = "
        UPDATE HOMEPAGE_PRODUCT_SETTING
        SET
            display_limit = ?
        WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $display_limit,
        $store_id
    ]);

    if ($stmt->rowCount() === 0) {

        $check = $pdo->prepare("
            SELECT setting_id
            FROM HOMEPAGE_PRODUCT_SETTING
            WHERE store_id = ?
        ");

        $check->execute([
            $store_id
        ]);

        if (!$check->fetch()) {
            throw new Exception(
                "Homepage product setting not found"
            );
        }
    }

    // 新增或修改商品類別
    if (isset($data["categories"])) {

        if (!is_array($data["categories"])) {
            throw new Exception(
                "Invalid categories"
            );
        }

        // 防止同一次 Request 出現重複名稱
        $submitted_category_names = [];

        foreach ($data["categories"] as $category) {

            if (!is_array($category)) {
                throw new Exception(
                    "Invalid category data"
                );
            }

            if (!isset($category["category_name"])) {
                throw new Exception(
                    "Category name is required"
                );
            }

            $category_name =
                trim($category["category_name"]);

            if ($category_name === "") {
                throw new Exception(
                    "Category name cannot be empty"
                );
            }

            if (mb_strlen($category_name) > 255) {
                throw new Exception(
                    "Category name is too long"
                );
            }

            $name_key =
                mb_strtolower($category_name);

            if (
                in_array(
                    $name_key,
                    $submitted_category_names,
                    true
                )
            ) {
                throw new Exception(
                    "Category name duplicated in request"
                );
            }

            $submitted_category_names[] =
                $name_key;

            // 修改既有 Category
            if (isset($category["category_id"])) {

                $category_id =
                    $category["category_id"];

                if (
                    !is_numeric($category_id) ||
                    floor((float)$category_id)
                    != (float)$category_id
                ) {
                    throw new Exception(
                        "Invalid category ID"
                    );
                }

                $category_id =
                    (int)$category_id;

                if ($category_id <= 0) {
                    throw new Exception(
                        "Invalid category ID"
                    );
                }

                // 確認 Category 屬於目前 Store
                $sql = "
                    SELECT
                        category_id
                    FROM CATEGORY
                    WHERE category_id = ?
                    AND store_id = ?
                    AND status != 'deleted'
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $category_id,
                    $store_id
                ]);

                if (!$stmt->fetch()) {
                    throw new Exception(
                        "Category not found"
                    );
                }

                // 檢查修改後的名稱是否與其他 Category 重複
                $sql = "
                    SELECT
                        category_id
                    FROM CATEGORY
                    WHERE store_id = ?
                    AND category_name = ?
                    AND category_id != ?
                    AND status != 'deleted'
                    LIMIT 1
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $store_id,
                    $category_name,
                    $category_id
                ]);

                if ($stmt->fetch()) {
                    throw new Exception(
                        "Category name already exists"
                    );
                }

                // 更新 Category
                // 不修改 sort_order
                $sql = "
                    UPDATE CATEGORY
                    SET
                        category_name = ?,
                        updated_at = NOW()
                    WHERE category_id = ?
                    AND store_id = ?
                    AND status != 'deleted'
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $category_name,
                    $category_id,
                    $store_id
                ]);

            } else {

                // 新增 Category

                // 檢查資料庫是否已有相同名稱
                $sql = "
                    SELECT
                        category_id
                    FROM CATEGORY
                    WHERE store_id = ?
                    AND category_name = ?
                    AND status != 'deleted'
                    LIMIT 1
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $store_id,
                    $category_name
                ]);

                if ($stmt->fetch()) {
                    throw new Exception(
                        "Category name already exists"
                    );
                }

                // 取得下一個 sort_order
                $sql = "
                    SELECT
                        COALESCE(
                            MAX(sort_order),
                            0
                        ) + 1
                    FROM CATEGORY
                    WHERE store_id = ?
                    AND status != 'deleted'
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $store_id
                ]);

                $sort_order =
                    (int)$stmt->fetchColumn();

                // 新增 Category
                $sql = "
                    INSERT INTO CATEGORY
                    (
                        store_id,
                        category_name,
                        sort_order,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'active'
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $store_id,
                    $category_name,
                    $sort_order
                ]);
            }
        }
    }

    // 刪除商品類別
    if (isset($data["deleted_category_ids"])) {

        if (
            !is_array(
                $data["deleted_category_ids"]
            )
        ) {
            throw new Exception(
                "Invalid deleted category IDs"
            );
        }

        foreach (
            $data["deleted_category_ids"]
            as $category_id
        ) {

            if (
                !is_numeric($category_id) ||
                floor((float)$category_id)
                != (float)$category_id
            ) {
                throw new Exception(
                    "Invalid category ID"
                );
            }

            $category_id =
                (int)$category_id;

            if ($category_id <= 0) {
                throw new Exception(
                    "Invalid category ID"
                );
            }

            // 確認 Category 屬於目前 Store
            $sql = "
                SELECT
                    category_id
                FROM CATEGORY
                WHERE category_id = ?
                AND store_id = ?
                AND status != 'deleted'
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $category_id,
                $store_id
            ]);

            if (!$stmt->fetch()) {
                throw new Exception(
                    "Category not found"
                );
            }

            // Soft Delete
            $sql = "
                UPDATE CATEGORY
                SET
                    status = 'deleted',
                    updated_at = NOW()
                WHERE category_id = ?
                AND store_id = ?
                AND status != 'deleted'
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $category_id,
                $store_id
            ]);
        }

        // 刪除完成後重新整理 Category sort_order
        $sql = "
            SELECT
                category_id
            FROM CATEGORY
            WHERE store_id = ?
            AND status != 'deleted'
            ORDER BY sort_order ASC, category_id ASC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $store_id
        ]);

        $remaining_categories =
            $stmt->fetchAll(PDO::FETCH_COLUMN);

        $new_sort_order = 1;

        foreach (
            $remaining_categories
            as $remaining_category_id
        ) {

            $sql = "
                UPDATE CATEGORY
                SET
                    sort_order = ?,
                    updated_at = NOW()
                WHERE category_id = ?
                AND store_id = ?
                AND status != 'deleted'
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $new_sort_order,
                $remaining_category_id,
                $store_id
            ]);

            $new_sort_order++;
        }
    }

    // 更新 Footer 設定
    if (!isset($data["footer"])) {
        throw new Exception(
            "Footer setting is required"
        );
    }

    if (!is_array($data["footer"])) {
        throw new Exception(
            "Invalid footer setting"
        );
    }

    $footer_fields = [
        "contact_phone_enable",
        "address_enable",
        "email_enable",
        "service_phone_enable"
    ];

    foreach ($footer_fields as $field) {

        if (!isset($data["footer"][$field])) {
            throw new Exception(
                "$field is required"
            );
        }

        $value =
            (int)$data["footer"][$field];

        if (
            !in_array(
                $value,
                [0, 1],
                true
            )
        ) {
            throw new Exception(
                "Invalid footer setting"
            );
        }
    }

    $contact_phone_enable =
        (int)$data["footer"]["contact_phone_enable"];

    $address_enable =
        (int)$data["footer"]["address_enable"];

    $email_enable =
        (int)$data["footer"]["email_enable"];

    $service_phone_enable =
        (int)$data["footer"]["service_phone_enable"];

    $sql = "
        UPDATE FOOTER_SETTING
        SET
            contact_phone_enable = ?,
            address_enable = ?,
            email_enable = ?,
            service_phone_enable = ?
        WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $contact_phone_enable,
        $address_enable,
        $email_enable,
        $service_phone_enable,
        $store_id
    ]);

    if ($stmt->rowCount() === 0) {

        $check = $pdo->prepare("
            SELECT footer_id
            FROM FOOTER_SETTING
            WHERE store_id = ?
        ");

        $check->execute([
            $store_id
        ]);

        if (!$check->fetch()) {
            throw new Exception(
                "Footer setting not found"
            );
        }
    }

    // Commit
    $pdo->commit();

    echo json_encode([
        "message" =>
            "Homepage settings updated successfully"
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