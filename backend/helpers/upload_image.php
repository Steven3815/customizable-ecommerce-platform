<?php

function uploadImage($file, $folder)
{
    // 檢查是否有上傳檔案
    if (
        !isset($file) ||
        $file["error"] !== UPLOAD_ERR_OK
    ) {
        throw new Exception("Image upload failed");
    }

    // 檢查 MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime_type = finfo_file(
        $finfo,
        $file["tmp_name"]
    );

    finfo_close($finfo);

    // 允許的圖片格式
    $allowed_types = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed_types[$mime_type])) {
        throw new Exception("Invalid image type");
    }

    // 圖片副檔名
    $extension = $allowed_types[$mime_type];

    // 建立資料夾
    $upload_dir =
        __DIR__
        . "/../uploads/"
        . $folder
        . "/";

    if (!is_dir($upload_dir)) {

        if (!mkdir(
            $upload_dir,
            0777,
            true
        )) {
            throw new Exception(
                "Failed to create upload directory"
            );
        }
    }

    // 建立唯一檔名
    $file_name =
        uniqid("image_", true)
        . "."
        . $extension;

    // 實際儲存位置
    $file_path =
        $upload_dir
        . $file_name;

    // 移動檔案
    if (!move_uploaded_file(
        $file["tmp_name"],
        $file_path
    )) {

        throw new Exception(
            "Failed to save image"
        );
    }

    // 回傳給資料庫儲存的 URL
    return "/uploads/"
        . $folder
        . "/"
        . $file_name;
}

/**
 * 刪除已上傳的圖片
 *
 * 用於資料庫 transaction rollback 時，
 * 清除已經成功上傳但沒有成功寫入資料庫的圖片。
 */
function deleteImage($image_url)
{
    if (
        !$image_url ||
        !is_string($image_url)
    ) {
        return;
    }

    // URL：
    // /uploads/products/image_xxx.jpg
    //
    // 轉成實際檔案位置：
    // __DIR__ . "/../uploads/products/image_xxx.jpg"

    $prefix = "/uploads/";

    if (
        strpos($image_url, $prefix) !== 0
    ) {
        return;
    }

    // 去掉 /uploads/
    $relative_path =
        substr(
            $image_url,
            strlen($prefix)
        );

    // 避免 ../ 路徑攻擊
    if (
        strpos($relative_path, "..") !== false
    ) {
        return;
    }

    $file_path =
        __DIR__
        . "/../uploads/"
        . $relative_path;

    // 確認是檔案後才刪除
    if (is_file($file_path)) {
        unlink($file_path);
    }
}
?>