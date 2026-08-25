<?php

// Store 取得商品管理資料

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../middleware/store_auth.php";

$per_page = 50;

// 取得頁數
$page =
    isset($_GET["page"]) &&
    $_GET["page"] !== ""
        ? $_GET["page"]
        : 1;

// 檢查頁數
if (
    !is_numeric($page) ||
    floor((float)$page) != (float)$page ||
    (int)$page < 1
) {
    echo json_encode([
        "error" => "Invalid page"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$page = (int)$page;

// 取得篩選條件
$category_id =
    isset($_GET["category_id"]) &&
    $_GET["category_id"] !== ""
        ? $_GET["category_id"]
        : null;

$status =
    isset($_GET["status"]) &&
    $_GET["status"] !== ""
        ? $_GET["status"]
        : null;

$stock_status =
    isset($_GET["stock_status"]) &&
    $_GET["stock_status"] !== ""
        ? $_GET["stock_status"]
        : null;

// 檢查 Category ID
if ($category_id !== null) {

    if (
        !is_numeric($category_id) ||
        floor((float)$category_id)
            != (float)$category_id
    ) {
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $category_id = (int)$category_id;

    if ($category_id <= 0) {
        echo json_encode([
            "error" => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 確認 Category 屬於目前 Store
    $sql = "
    SELECT
        category_id,
        category_name
    FROM CATEGORY
    WHERE category_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $category_id,
        $store_id
    ]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode([
            "error" => "Category does not belong to this store"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 檢查 Status
if ($status !== null) {

    if (
        $status !== "active" &&
        $status !== "inactive"
    ) {
        echo json_encode([
            "error" => "Invalid status"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 檢查 Stock Status
if ($stock_status !== null) {

    if (
        $stock_status !== "in_stock" &&
        $stock_status !== "low_stock" &&
        $stock_status !== "out_of_stock"
    ) {
        echo json_encode([
            "error" => "Invalid stock status"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

try {

    // 取得 Store 庫存預警門檻
    $sql = "
    SELECT
        stock_alert_threshold,
        spec_stock_alert_threshold
    FROM STORE_SETTING
    WHERE store_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);
    $store_setting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$store_setting) {
        echo json_encode([
            "error" => "Store setting not found"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 一般商品庫存預警門檻
    $stock_alert_threshold =
        $store_setting["stock_alert_threshold"] !== null
            ? (int)$store_setting["stock_alert_threshold"]
            : 0;

    // 商品規格庫存預警門檻
    $spec_stock_alert_threshold =
        $store_setting["spec_stock_alert_threshold"] !== null
            ? (int)$store_setting["spec_stock_alert_threshold"]
            : 0;

    // 建立 Product WHERE 條件
    $where = [
        "p.store_id = ?"
    ];

    $params = [$store_id];

    // Category 篩選
    if ($category_id !== null) {

        $where[] = "p.category_id = ?";
        $params[] = $category_id;
    }

    // Status 篩選
    if ($status !== null) {

        $where[] = "p.status = ?";
        $params[] = $status;
    }

    $where_sql =
        implode(" AND ", $where);

    // 取得商品
    $sql = "
    SELECT
        p.product_id,
        p.store_id,
        p.category_id,
        c.category_name,
        p.product_name,
        p.description,
        p.price,
        p.stock,
        p.has_spec,
        p.spec_name,
        p.sort_order,
        p.status
    FROM PRODUCT p
    LEFT JOIN CATEGORY c
        ON p.category_id = c.category_id
        AND c.store_id = p.store_id
    WHERE $where_sql
    ORDER BY
        p.sort_order ASC,
        p.product_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result_products = [];

    foreach ($products as $product) {

        $product_id = (int)$product["product_id"];
        $has_spec = (bool)$product["has_spec"];

        // 無規格商品
        if (!$has_spec) {

            $stock =
                $product["stock"] !== null
                    ? (int)$product["stock"]
                    : 0;

            // 判斷庫存狀態
            if ($stock <= 0) {

                $current_stock_status = "out_of_stock";

            } elseif (
                $stock <= $stock_alert_threshold
            ) {

                $current_stock_status = "low_stock";

            } else {

                $current_stock_status = "in_stock";
            }

            // Stock Status 篩選
            if (
                $stock_status !== null &&
                $stock_status !==
                    $current_stock_status
            ) {
                continue;
            }

            $result_products[] = [
                "product_id" => $product_id,
                "store_id" => (int)$product["store_id"],
                "category_id" => $product["category_id"] !== null
                    ? (int)$product["category_id"]
                    : null,
                "category_name" => $product["category_name"],
                "product_name" => $product["product_name"],
                "has_spec" => false,
                "spec_name" => null,
                "spec_id" => null,
                "spec_value" => null,
                "price" => $product["price"] !== null
                    ? (float)$product["price"]
                    : null,
                "stock" => $stock,
                "stock_status" => $current_stock_status,
                "sort_order" => $product["sort_order"] !== null
                    ? (int)$product["sort_order"]
                    : null,
                "status" => $product["status"]
            ];

            continue;
        }

        // 有規格商品
        $sql = "
        SELECT
            spec_id,
            spec_name,
            price,
            stock,
            status,
            created_at,
            updated_at
        FROM PRODUCT_SPEC
        WHERE product_id = ?
        AND status = 'active'
        ORDER BY
            spec_id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
        $specs =$stmt->fetchAll(PDO::FETCH_ASSOC);

        // 沒有規格資料
        if (!$specs) {
            continue;
        }

        // 每個規格算一筆
        foreach ($specs as $spec) {

            $spec_id = (int)$spec["spec_id"];

            $spec_stock = (int)$spec["stock"];

            // 判斷規格庫存狀態
            if ($spec_stock <= 0) {

                $current_stock_status = "out_of_stock";

            } elseif (
                $spec_stock <= $spec_stock_alert_threshold
            ) {

                $current_stock_status = "low_stock";

            } else {

                $current_stock_status = "in_stock";
            }

            // Stock Status 篩選
            if (
                $stock_status !== null &&
                $stock_status !==
                    $current_stock_status
            ) {
                continue;
            }

            $result_products[] = [
                "product_id" => $product_id,
                "store_id" => (int)$product["store_id"],
                "category_id" => $product["category_id"] !== null
                    ? (int)$product["category_id"]
                    : null,
                "category_name" => $product["category_name"],
                "product_name" => $product["product_name"],
                "has_spec" => true,

                // 規格名稱 PRODUCT.spec_name
                "spec_name" => $product["spec_name"],

                "spec_id" => $spec_id,

                // 規格值 PRODUCT_SPEC.spec_name
                "spec_value" => $spec["spec_name"],

                "price" => $spec["price"] !== null
                    ? (float)$spec["price"]
                    : null,

                "stock" => $spec_stock,
                "stock_status" => $current_stock_status,

                "sort_order" => $product["sort_order"] !== null
                    ? (int)$product["sort_order"]
                    : null,

                "status" => $product["status"]
            ];
        }
    }

    // 符合條件的總筆數
    // 有規格商品會按照規格數量計算
    $total = count($result_products);

    // 計算總頁數
    $total_pages =
        $total > 0
            ? (int)ceil($total / $per_page)
            : 0;

    // 如果沒有資料
    if ($total === 0) {

        echo json_encode([
            "message" => "No products found",
            "store_id" => $store_id,
            "category_id" => $category_id,
            "status" => $status,
            "stock_status" => $stock_status,

            "pagination" => [
                "current_page" => $page,
                "per_page" => $per_page,
                "total" => 0,
                "total_pages" => 0,
                "has_previous_page" => false,
                "has_next_page" => false
            ],

            "products" => []
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 頁數超過範圍
    if ($page > $total_pages) {

        echo json_encode([
            "error" => "Page out of range",
            "total_pages" => $total_pages
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 計算起始位置
    $offset = ($page - 1) * $per_page;

    // 取得目前頁面的 50 筆
    $paged_products =
        array_slice(
            $result_products,
            $offset,
            $per_page
        );

    // 回傳
    echo json_encode([
        "message" => "Product management data retrieved successfully",
        "store_id" => $store_id,
        "category_id" => $category_id,
        "status" => $status,
        "stock_status" => $stock_status,

        "pagination" => [
            "current_page" => $page,
            "per_page" => $per_page,
            "total" => $total,
            "total_pages" => $total_pages,
            "has_previous_page" => $page > 1,
            "has_next_page" => $page < $total_pages
        ],

        "products" => $paged_products
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>