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

    $max_file_size = 800 * 1024;

    if ($width <= 1500 && $file_type === 'image/jpeg' && filesize($file_path) <= $max_file_size) {
        return $upload; // Already optimized
    }

    $new_width = min($width, 1500);
    $new_height = (int) round(($height / $width) * $new_width);

    // Create image resource
    $image = ($file_type === 'image/png') ? imagecreatefrompng($file_path) : imagecreatefromjpeg($file_path);
    $resized = imagecreatetruecolor($new_width, $new_height);

    if ($file_type === 'image/png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }

    imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagedestroy($image);

    // PNG is lossless, so hitting an 800KB cap on a large/detailed PNG means
    // either heavy downscaling or converting to JPEG. JPEG's quality knob
    // preserves far more visual detail at a given file size than shrinking
    // dimensions does, so convert to JPEG instead (flattened onto white,
    // since JPEG has no alpha channel).
    $original_path = $file_path;
    if ($file_type === 'image/png') {
        $flattened = imagecreatetruecolor($new_width, $new_height);
        $white = imagecolorallocate($flattened, 255, 255, 255);
        imagefill($flattened, 0, 0, $white);
        imagecopy($flattened, $resized, 0, 0, 0, 0, $new_width, $new_height);
        imagedestroy($resized);
        $resized = $flattened;

        $new_basename = wp_unique_filename(dirname($file_path), preg_replace('/\.png$/i', '.jpg', basename($file_path)));
        $file_path = trailingslashit(dirname($file_path)) . $new_basename;
        $file_type = 'image/jpeg';

        $upload['file'] = $file_path;
        $upload['type'] = 'image/jpeg';
        if (!empty($upload['url'])) {
            $upload['url'] = str_replace(basename($original_path), $new_basename, $upload['url']);
        }
    }

    $quality = 85; // JPEG quality: 0 (low) to 100 (best)
    imagejpeg($resized, $file_path, $quality);

    // Step quality down until the file fits under the size cap
    while (filesize($file_path) > $max_file_size && $quality > 20) {
        $quality -= 10;
        imagejpeg($resized, $file_path, $quality);
    }

    // Quality alone can't always guarantee the cap for very detailed images
    // at the quality floor, so keep shrinking dimensions as a last resort.
    $current_width = $new_width;
    $current_height = $new_height;
    $attempts = 0;

    while (filesize($file_path) > $max_file_size && $attempts < 10 && $current_width > 100) {
        $attempts++;
        $current_width = (int) round($current_width * 0.85);
        $current_height = (int) round($current_height * 0.85);

        $scaled = imagecreatetruecolor($current_width, $current_height);
        imagecopyresampled($scaled, $resized, 0, 0, 0, 0, $current_width, $current_height, imagesx($resized), imagesy($resized));
        imagedestroy($resized);
        $resized = $scaled;

        imagejpeg($resized, $file_path, 70);
    }

    imagedestroy($resized);

    // If we converted PNG -> JPEG, the original PNG file is now orphaned.
    if ($file_path !== $original_path && file_exists($original_path)) {
        unlink($original_path);
    }

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

    $original_path = $path;
    $original_url = wp_get_attachment_url($post_ID);
    $result = auto_resize_uploaded_images(['file' => $path, 'url' => $original_url]);

    // If the file was converted (e.g. PNG -> JPEG), the attachment post
    // still points at the old path/URL/mime type — bring it in sync.
    if (!empty($result['file']) && $result['file'] !== $original_path) {
        update_attached_file($post_ID, $result['file']);
        wp_update_post([
            'ID' => $post_ID,
            'post_mime_type' => $result['type'],
            'guid' => $result['url'],
        ]);
    }
}
