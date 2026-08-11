<?php

// Store 更新商品

header("Content-Type: application/json; charset=UTF-8");

require_once "../../../config/database.php";
require_once "../../../helpers/upload_image.php";

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

// 檢查 product_id
$product_id = $_POST["product_id"] ?? null;

if ($product_id === null || $product_id === "") {
    echo json_encode([
        "error" => "Product ID is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !is_numeric($product_id) ||
    floor((float)$product_id) != (float)$product_id
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

// 取得基本資料
$category_id = $_POST["category_id"] ?? null;
$product_name = trim($_POST["product_name"] ?? "");
$description = trim($_POST["description"] ?? "");
$price = $_POST["price"] ?? null;
$stock = $_POST["stock"] ?? null;
$has_spec = isset($_POST["has_spec"]) ? (int)$_POST["has_spec"] : 0;
$status = $_POST["status"] ?? "active";

// 檢查必要欄位
if (
    $category_id === null ||
    $category_id === "" ||
    $product_name === ""
) {
    echo json_encode([
        "error" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查 Category ID
if (
    !is_numeric($category_id) ||
    floor((float)$category_id) != (float)$category_id
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

// 檢查商品名稱
if (mb_strlen($product_name) > 255) {
    echo json_encode([
        "error" => "Product name is too long"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查 has_spec
if ($has_spec !== 0 && $has_spec !== 1) {
    echo json_encode([
        "error" => "Invalid has_spec"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查價格與庫存
if ($has_spec === 0) {
    if (
        $price === null ||
        $price === "" ||
        !is_numeric($price) ||
        (float)$price < 0
    ) {
        echo json_encode([
            "error" => "Invalid price"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (
        $stock === null ||
        $stock === "" ||
        !is_numeric($stock) ||
        (float)$stock < 0 ||
        floor((float)$stock) != (float)$stock
    ) {
        echo json_encode([
            "error" => "Invalid stock"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $price = (float)$price;
    $stock = (int)$stock;
} else {
    $price = null;
    $stock = 0;
}

// 檢查商品狀態
$allowed_status = [
    "active",
    "hidden"
];

if (!in_array($status, $allowed_status, true)) {
    echo json_encode([
        "error" => "Invalid product status"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查 Category 是否屬於目前 Store
$sql = "
SELECT category_id
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
        "error" => "Category not found"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查商品是否屬於目前 Store
$sql = "
SELECT
    product_id,
    has_spec,
    status
FROM PRODUCT
WHERE product_id = ?
AND store_id = ?
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

// deleted 商品禁止更新
if ($product["status"] === "deleted") {
    echo json_encode([
        "error" => "Deleted product cannot be updated"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得並驗證商品規格
$specs = [];

if ($has_spec === 1) {
    if (
        !isset($_POST["specs"]) ||
        !is_array($_POST["specs"])
    ) {
        echo json_encode([
            "error" => "Specifications are required"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $specs = $_POST["specs"];

    if (empty($specs)) {
        echo json_encode([
            "error" => "At least one specification is required"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $submitted_spec_ids_check = [];

    foreach ($specs as $index => $spec) {
        if (!is_array($spec)) {
            echo json_encode([
                "error" => "Invalid specification data"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $spec_id = $spec["spec_id"] ?? null;

        if ($spec_id === null || $spec_id === "") {
            $spec_id = null;
        } else {
            if (
                !is_numeric($spec_id) ||
                floor((float)$spec_id) != (float)$spec_id
            ) {
                echo json_encode([
                    "error" => "Invalid specification ID"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $spec_id = (int)$spec_id;

            if ($spec_id <= 0) {
                echo json_encode([
                    "error" => "Invalid specification ID"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (
                in_array(
                    $spec_id,
                    $submitted_spec_ids_check,
                    true
                )
            ) {
                echo json_encode([
                    "error" => "Duplicate specification ID"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $submitted_spec_ids_check[] = $spec_id;
        }

        $spec_name = trim($spec["spec_name"] ?? "");

        if ($spec_name === "") {
            echo json_encode([
                "error" => "Specification name is required"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (mb_strlen($spec_name) > 255) {
            echo json_encode([
                "error" => "Specification name is too long"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $spec_price = $spec["price"] ?? null;

        if (
            $spec_price === null ||
            $spec_price === "" ||
            !is_numeric($spec_price) ||
            (float)$spec_price < 0
        ) {
            echo json_encode([
                "error" => "Invalid specification price"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $spec_stock = $spec["stock"] ?? null;

        if (
            $spec_stock === null ||
            $spec_stock === "" ||
            !is_numeric($spec_stock) ||
            (float)$spec_stock < 0 ||
            floor((float)$spec_stock) != (float)$spec_stock
        ) {
            echo json_encode([
                "error" => "Invalid specification stock"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $spec_status = $spec["status"] ?? "active";

        if (!in_array($spec_status, ["active", "inactive"], true)) {
            echo json_encode([
                "error" => "Invalid specification status"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $specs[$index] = [
            "spec_id" => $spec_id,
            "spec_name" => $spec_name,
            "price" => (float)$spec_price,
            "stock" => (int)$spec_stock,
            "status" => $spec_status
        ];
    }
}

// 初始化圖片
$uploaded_file_paths = [];

// 開始交易
$pdo->beginTransaction();

try {
    // 更新商品基本資料
    $sql = "
    UPDATE PRODUCT
    SET
        category_id = ?,
        product_name = ?,
        description = ?,
        price = ?,
        stock = ?,
        has_spec = ?,
        status = ?,
        updated_at = NOW()
    WHERE product_id = ?
    AND store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $category_id,
        $product_name,
        $description,
        $price,
        $stock,
        $has_spec,
        $status,
        $product_id,
        $store_id
    ]);

    // 更新商品規格
    if ($has_spec === 1) {
        $existing_spec_ids = [];

        $sql = "
        SELECT
            spec_id,
            spec_name
        FROM PRODUCT_SPEC
        WHERE product_id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);

        $existing_specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existing_specs as $existing_spec) {
            $existing_spec_ids[] = (int)$existing_spec["spec_id"];
        }

        $submitted_spec_ids = [];

        foreach ($specs as $spec) {
            if ($spec["spec_id"] !== null) {
                if (
                    !in_array(
                        $spec["spec_id"],
                        $existing_spec_ids,
                        true
                    )
                ) {
                    throw new Exception(
                        "Invalid specification ID"
                    );
                }

                $submitted_spec_ids[] = $spec["spec_id"];

                $sql = "
                UPDATE PRODUCT_SPEC
                SET
                    spec_name = ?,
                    price = ?,
                    stock = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE spec_id = ?
                AND product_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $spec["spec_name"],
                    $spec["price"],
                    $spec["stock"],
                    $spec["status"],
                    $spec["spec_id"],
                    $product_id
                ]);
            } else {
                // 避免建立重複的商品規格
                $sql = "
                SELECT spec_id
                FROM PRODUCT_SPEC
                WHERE product_id = ?
                AND spec_name = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $product_id,
                    $spec["spec_name"]
                ]);

                $existing_spec = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_spec) {
                    throw new Exception(
                        "Specification already exists. Please use the existing specification ID."
                    );
                }

                $sql = "
                INSERT INTO PRODUCT_SPEC
                (
                    product_id,
                    spec_name,
                    price,
                    stock,
                    status
                )
                VALUES (?, ?, ?, ?, ?)
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $product_id,
                    $spec["spec_name"],
                    $spec["price"],
                    $spec["stock"],
                    $spec["status"]
                ]);

                $submitted_spec_ids[] = (int)$pdo->lastInsertId();
            }
        }

        foreach ($existing_spec_ids as $existing_spec_id) {
            if (
                !in_array(
                    $existing_spec_id,
                    $submitted_spec_ids,
                    true
                )
            ) {
                // 不刪除規格，只改為 inactive
                // 避免 CART_ITEM 或 ORDER_ITEM 等資料仍然參照此 spec_id
                $sql = "
                UPDATE PRODUCT_SPEC
                SET
                    status = 'inactive',
                    updated_at = NOW()
                WHERE spec_id = ?
                AND product_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $existing_spec_id,
                    $product_id
                ]);
            }
        }
    } else {
        // 商品改成沒有規格時，不刪除原本規格
        // 只將所有規格設為 inactive
        $sql = "
        UPDATE PRODUCT_SPEC
        SET
            status = 'inactive',
            updated_at = NOW()
        WHERE product_id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    }

    // 新增商品圖片
    if (
        isset($_FILES["images"]) &&
        isset($_FILES["images"]["name"]) &&
        is_array($_FILES["images"]["name"])
    ) {
        $file_count = count($_FILES["images"]["name"]);

        for ($i = 0; $i < $file_count; $i++) {
            if (
                $_FILES["images"]["error"][$i]
                === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            $file = [
                "name" => $_FILES["images"]["name"][$i],
                "type" => $_FILES["images"]["type"][$i],
                "tmp_name" => $_FILES["images"]["tmp_name"][$i],
                "error" => $_FILES["images"]["error"][$i],
                "size" => $_FILES["images"]["size"][$i]
            ];

            $image_url = uploadImage(
                $file,
                "products"
            );

            $relative_path = ltrim($image_url, "/");

            $file_path =
                dirname(__DIR__, 3)
                . "/"
                . $relative_path;

            $uploaded_file_paths[] = $file_path;

            // 新增圖片時使用前端傳入的排序
            $sort_order = isset($_POST["image_sort_orders"][$i])
                ? (int)$_POST["image_sort_orders"][$i]
                : $i;

            if ($sort_order < 0) {
                throw new Exception(
                    "Invalid image sort order"
                );
            }

            $sql = "
            INSERT INTO PRODUCT_IMAGE
            (
                product_id,
                image_url,
                sort_order
            )
            VALUES (?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $product_id,
                $image_url,
                $sort_order
            ]);
        }
    }

    // 更新既有圖片排序
    if (
        isset($_POST["image_orders"]) &&
        is_array($_POST["image_orders"])
    ) {
        foreach ($_POST["image_orders"] as $image_order) {
            if (!is_array($image_order)) {
                throw new Exception(
                    "Invalid image order data"
                );
            }

            $image_id = $image_order["image_id"] ?? null;
            $sort_order = $image_order["sort_order"] ?? null;

            if (
                $image_id === null ||
                !is_numeric($image_id) ||
                floor((float)$image_id) != (float)$image_id
            ) {
                throw new Exception(
                    "Invalid image ID"
                );
            }

            if (
                $sort_order === null ||
                !is_numeric($sort_order) ||
                floor((float)$sort_order) != (float)$sort_order ||
                (int)$sort_order < 0
            ) {
                throw new Exception(
                    "Invalid image sort order"
                );
            }

            $image_id = (int)$image_id;
            $sort_order = (int)$sort_order;

            // 確認圖片屬於目前商品
            $sql = "
            SELECT image_id
            FROM PRODUCT_IMAGE
            WHERE image_id = ?
            AND product_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $image_id,
                $product_id
            ]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception(
                    "Image does not belong to this product"
                );
            }

            // 更新排序
            $sql = "
            UPDATE PRODUCT_IMAGE
            SET sort_order = ?
            WHERE image_id = ?
            AND product_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $sort_order,
                $image_id,
                $product_id
            ]);
        }
    }

    // Commit
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($uploaded_file_paths as $file_path) {
        if (
            is_string($file_path) &&
            file_exists($file_path)
        ) {
            @unlink($file_path);
        }
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 重新取得更新後商品
try {
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
        p.status,
        p.created_at,
        p.updated_at
    FROM PRODUCT p
    LEFT JOIN CATEGORY c
        ON p.category_id = c.category_id
        AND c.store_id = p.store_id
    WHERE p.product_id = ?
    AND p.store_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $product_id,
        $store_id
    ]);

    $updated_product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$updated_product) {
        echo json_encode([
            "error" =>
                "Product updated, but failed to retrieve product"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 取得商品圖片
    $sql = "
    SELECT
        image_id,
        image_url,
        sort_order
    FROM PRODUCT_IMAGE
    WHERE product_id = ?
    ORDER BY sort_order ASC, image_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);

    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($images as &$image) {
        $image["image_id"] = (int)$image["image_id"];
        $image["sort_order"] = (int)$image["sort_order"];
    }

    unset($image);

    // 取得商品規格
    $specs = [];

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
    ORDER BY spec_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);

    $specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($specs as &$spec) {
        $spec["spec_id"] = (int)$spec["spec_id"];
        $spec["price"] = (float)$spec["price"];
        $spec["stock"] = (int)$spec["stock"];
    }

    unset($spec);

    // 整理商品資料
    $updated_product["product_id"] =
        (int)$updated_product["product_id"];

    $updated_product["store_id"] =
        (int)$updated_product["store_id"];

    $updated_product["category_id"] =
        $updated_product["category_id"] !== null
            ? (int)$updated_product["category_id"]
            : null;

    $updated_product["price"] =
        $updated_product["price"] !== null
            ? (float)$updated_product["price"]
            : null;

    $updated_product["stock"] =
        $updated_product["stock"] !== null
            ? (int)$updated_product["stock"]
            : null;

    $updated_product["has_spec"] =
        (bool)$updated_product["has_spec"];

    // 回傳
    echo json_encode([
        "message" => "Product updated successfully",
        "store_id" => $store_id,
        "product" => [
            "product_id" => $updated_product["product_id"],
            "category" => [
                "category_id" => $updated_product["category_id"],
                "category_name" => $updated_product["category_name"]
            ],
            "product_name" => $updated_product["product_name"],
            "description" => $updated_product["description"],
            "price" => $updated_product["price"],
            "stock" => $updated_product["stock"],
            "has_spec" => $updated_product["has_spec"],
            "specs" => $specs,
            "images" => $images,
            "status" => $updated_product["status"],
            "created_at" => $updated_product["created_at"],
            "updated_at" => $updated_product["updated_at"]
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        "error" =>
            "Product updated successfully, but failed to retrieve updated data",
        "detail" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

?>