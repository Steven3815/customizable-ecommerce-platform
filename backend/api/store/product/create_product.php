<?php

// Store 建立商品

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

$stmt->execute([
    $store_id
]);

$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode([
        "error" => "Store not found"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 取得基本資料
$category_id =
    $_POST["category_id"] ?? null;

$product_name =
    trim($_POST["product_name"] ?? "");

$description =
    trim($_POST["description"] ?? "");

$price =
    $_POST["price"] ?? null;

$stock =
    $_POST["stock"] ?? null;

// 是否開啟規格
// 預設關閉
$has_spec = isset($_POST["has_spec"])
    ? (int)$_POST["has_spec"]
    : 0;

// 商品建立時固定 active
$status = "active";

// 規格名稱
// 例如：尺寸、顏色
$spec_name =
    trim($_POST["spec_name"] ?? "");

// 檢查必要欄位
if (
    $category_id === null ||
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
    floor((float)$category_id) != (float)$category_id ||
    (int)$category_id <= 0
) {
    echo json_encode([
        "error" => "Invalid category ID"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$category_id = (int)$category_id;

// 檢查商品名稱
if (mb_strlen($product_name) > 200) {
    echo json_encode([
        "error" => "Product name is too long"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查 has_spec
if (
    $has_spec !== 0 &&
    $has_spec !== 1
) {
    echo json_encode([
        "error" => "Invalid has_spec"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 檢查商品價格
// 不論有沒有規格
// PRODUCT.price 都是商品價格
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

$price = (float)$price;

// 無規格商品
if ($has_spec === 0) {

    // 無規格時 PRODUCT.stock 必須填寫
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

    $stock = (int)$stock;

    // 沒有規格
    // PRODUCT.spec_name 為 NULL
    $spec_name = null;

} else {

    // 有規格商品

    // 規格類型名稱必填
    if ($spec_name === "") {
        echo json_encode([
            "error" => "Specification name is required"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (mb_strlen($spec_name) > 100) {
        echo json_encode([
            "error" => "Specification name is too long"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

// 檢查 Category 是否存在且屬於目前 Store
$sql = "
SELECT
    category_id
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

// 取得商品規格
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

    // 至少一個規格
    if (count($specs) < 1) {
        echo json_encode([
            "error" => "At least one specification is required"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 最多 10 個規格
    if (count($specs) > 10) {
        echo json_encode([
            "error" => "A product can have at most 10 specifications"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    foreach ($specs as $index => $spec) {

        if (!is_array($spec)) {
            echo json_encode([
                "error" => "Invalid specification data"
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // 實際規格值
        // 例如 S、M、L
        $spec_value =
            trim($spec["spec_name"] ?? "");

        // 規格庫存
        $spec_stock =
            $spec["stock"] ?? null;

        // 檢查規格值
        if ($spec_value === "") {
            echo json_encode([
                "error" => "Specification value is required"
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (mb_strlen($spec_value) > 100) {
            echo json_encode([
                "error" => "Specification value is too long"
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // 檢查規格庫存
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

        $spec_stock = (int)$spec_stock;

        // 建立規格資料
        // PRODUCT_SPEC.price 自動使用 PRODUCT.price
        $specs[$index] = [
            "spec_name" => $spec_value,
            "price" => $price,
            "stock" => $spec_stock,
            "status" => "active"
        ];
    }

    // 有規格時
    // 實際庫存由 PRODUCT_SPEC 管理
    $stock = 0;

} else {

    // 無規格時不建立 PRODUCT_SPEC
    $specs = [];
}

// 檢查商品圖片
if (
    !isset($_FILES["images"]) ||
    !isset($_FILES["images"]["name"]) ||
    !is_array($_FILES["images"]["name"])
) {
    echo json_encode([
        "error" => "At least one product image is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$file_count =
    count($_FILES["images"]["name"]);

$valid_image_count = 0;

for ($i = 0; $i < $file_count; $i++) {

    if (
        $_FILES["images"]["error"][$i]
        !== UPLOAD_ERR_NO_FILE
    ) {
        $valid_image_count++;
    }
}

if ($valid_image_count < 1) {
    echo json_encode([
        "error" => "At least one product image is required"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 開始交易
$pdo->beginTransaction();

try {

    // 建立商品
    $sql = "
    INSERT INTO PRODUCT
    (
        store_id,
        category_id,
        product_name,
        description,
        price,
        stock,
        has_spec,
        spec_name,
        status
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $store_id,
        $category_id,
        $product_name,
        $description,
        $price,
        $stock,
        $has_spec,
        $spec_name,
        $status
    ]);

    $product_id =
        (int)$pdo->lastInsertId();

    // 建立商品規格
    if ($has_spec === 1) {

        $sql = "
        INSERT INTO PRODUCT_SPEC
        (
            product_id,
            store_id,
            spec_name,
            price,
            stock,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($specs as $spec) {

            $stmt->execute([
                $product_id,
                $store_id,
                $spec["spec_name"],
                $price,
                $spec["stock"],
                "active"
            ]);
        }
    }

    // 建立商品圖片
    $uploaded_images = [];

    $sort_order = 1;

    for ($i = 0; $i < $file_count; $i++) {

        if (
            $_FILES["images"]["error"][$i]
            === UPLOAD_ERR_NO_FILE
        ) {
            continue;
        }

        $file = [
            "name" =>
                $_FILES["images"]["name"][$i],

            "type" =>
                $_FILES["images"]["type"][$i],

            "tmp_name" =>
                $_FILES["images"]["tmp_name"][$i],

            "error" =>
                $_FILES["images"]["error"][$i],

            "size" =>
                $_FILES["images"]["size"][$i]
        ];

        $image_url =
            uploadImage(
                $file,
                "products"
            );

        $sql = "
        INSERT INTO PRODUCT_IMAGE
        (
            product_id,
            store_id,
            image_url,
            sort_order
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
            $product_id,
            $store_id,
            $image_url,
            $sort_order
        ]);

        $image_id =
            (int)$pdo->lastInsertId();

        $uploaded_images[] = [
            "image_id" => $image_id,
            "image_url" => $image_url,
            "sort_order" => $sort_order
        ];

        $sort_order++;
    }

    // 完成交易
    $pdo->commit();

    // 回傳
    echo json_encode([
        "message" =>
            "Product created successfully",

        "store_id" =>
            $store_id,

        "product" => [
            "product_id" =>
                $product_id,

            "store_id" =>
                $store_id,

            "category_id" =>
                $category_id,

            "product_name" =>
                $product_name,

            "description" =>
                $description,

            "price" =>
                $price,

            "stock" =>
                $stock,

            "has_spec" =>
                (bool)$has_spec,

            "spec_name" =>
                $spec_name,

            "specs" =>
                $specs,

            "images" =>
                $uploaded_images,

            "status" =>
                $status
        ]

    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>