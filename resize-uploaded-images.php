<?php
add_filter('wp_handle_upload', 'auto_resize_uploaded_images');
function auto_resize_uploaded_images($upload)
{
    if (!get_option('haysky_resize_uploaded_images', 0)) {
        return $upload; // If the option is not enabled, skip processing
    }
    $file_path = $upload['file'];
    $file_type = mime_content_type($file_path);

    // Only process JPEG or PNG
    if (!in_array($file_type, ['image/jpeg', 'image/png'])) {
        return $upload;
    }

    list($width, $height) = getimagesize($file_path);

    if ($width <= 1500 && filesize($file_path) <= 300 * 1024) {
        return $upload; // Already optimized
    }

    $new_width = min($width, 1500);
    $new_height = ($height / $width) * $new_width;

    // Create image resource
    $image = ($file_type === 'image/png') ? imagecreatefrompng($file_path) : imagecreatefromjpeg($file_path);
    $resized = imagecreatetruecolor($new_width, $new_height);

    imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Overwrite the original with compressed version
    if ($file_type === 'image/png') {
        imagepng($resized, $file_path, 9); // PNG compression level: 0 (no) to 9 (max)
    } else {
        imagejpeg($resized, $file_path, 85); // JPEG quality: 0 (low) to 100 (best)
    }

    imagedestroy($image);
    imagedestroy($resized);

    return $upload;
}

add_action('add_attachment', 'auto_resize_uploaded_images_on_add');
function auto_resize_uploaded_images_on_add($post_ID)
{
    if (!get_option('haysky_resize_uploaded_images', 0)) {
        return; // If the option is not enabled, skip processing
    }
    $path = get_attached_file($post_ID);
    if (!$path) return;
    auto_resize_uploaded_images(['file' => $path]);
}
