<?php

function uploadImage(
    $file,
    $folder,
    $resize_width = null,
    $resize_height = null
) {
    // 檢查是否有上傳檔案
    if (
        !isset($file) ||
        !is_array($file) ||
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

    // 取得圖片尺寸
    $image_info = getimagesize(
        $file["tmp_name"]
    );

    if ($image_info === false) {
        throw new Exception("Invalid image file");
    }

    $original_width = $image_info[0];
    $original_height = $image_info[1];

    if (
        $original_width <= 0 ||
        $original_height <= 0
    ) {
        throw new Exception("Invalid image dimensions");
    }

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

    /*
     * 沒有指定 resize
     * → 保留原始圖片
     */
    if (
        $resize_width === null ||
        $resize_height === null
    ) {

        if (!move_uploaded_file(
            $file["tmp_name"],
            $file_path
        )) {

            throw new Exception(
                "Failed to save image"
            );
        }

    } else {

        /*
         * 指定 resize
         * → 自動裁切成指定比例
         */

        if (
            $resize_width <= 0 ||
            $resize_height <= 0
        ) {
            throw new Exception(
                "Invalid resize dimensions"
            );
        }

        // 建立來源圖片
        switch ($mime_type) {

            case "image/jpeg":
                $source_image =
                    imagecreatefromjpeg(
                        $file["tmp_name"]
                    );
                break;

            case "image/png":
                $source_image =
                    imagecreatefrompng(
                        $file["tmp_name"]
                    );
                break;

            case "image/webp":
                $source_image =
                    imagecreatefromwebp(
                        $file["tmp_name"]
                    );
                break;

            default:
                throw new Exception(
                    "Unsupported image type"
                );
        }

        if ($source_image === false) {
            throw new Exception(
                "Failed to create source image"
            );
        }

        /*
         * 計算來源圖片比例
         */
        $source_ratio =
            $original_width /
            $original_height;

        $target_ratio =
            $resize_width /
            $resize_height;

        /*
         * 計算裁切範圍
         */
        if ($source_ratio > $target_ratio) {

            // 原圖太寬
            $crop_height =
                $original_height;

            $crop_width =
                (int)(
                    $original_height
                    * $target_ratio
                );

            $crop_x =
                (int)(
                    ($original_width - $crop_width)
                    / 2
                );

            $crop_y = 0;

        } else {

            // 原圖太高
            $crop_width =
                $original_width;

            $crop_height =
                (int)(
                    $original_width
                    / $target_ratio
                );

            $crop_x = 0;

            $crop_y =
                (int)(
                    ($original_height - $crop_height)
                    / 2
                );
        }

        // 建立新的圖片
        $new_image =
            imagecreatetruecolor(
                $resize_width,
                $resize_height
            );

        /*
         * PNG / WebP 保留透明背景
         */
        if (
            $mime_type === "image/png" ||
            $mime_type === "image/webp"
        ) {

            imagealphablending(
                $new_image,
                false
            );

            imagesavealpha(
                $new_image,
                true
            );

            $transparent =
                imagecolorallocatealpha(
                    $new_image,
                    0,
                    0,
                    0,
                    127
                );

            imagefill(
                $new_image,
                0,
                0,
                $transparent
            );
        }

        // 裁切並縮放
        imagecopyresampled(
            $new_image,
            $source_image,

            0,
            0,

            $crop_x,
            $crop_y,

            $resize_width,
            $resize_height,

            $crop_width,
            $crop_height
        );

        // 儲存圖片
        switch ($mime_type) {

            case "image/jpeg":

                imagejpeg(
                    $new_image,
                    $file_path,
                    90
                );

                break;

            case "image/png":

                imagepng(
                    $new_image,
                    $file_path,
                    6
                );

                break;

            case "image/webp":

                imagewebp(
                    $new_image,
                    $file_path,
                    90
                );

                break;
        }

        // 釋放記憶體
        imagedestroy(
            $source_image
        );

        imagedestroy(
            $new_image
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
 */
function deleteImage($image_url)
{
    if (
        !$image_url ||
        !is_string($image_url)
    ) {
        return;
    }

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

    if (is_file($file_path)) {
        unlink($file_path);
    }
}

?>