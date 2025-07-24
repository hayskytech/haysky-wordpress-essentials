<?php
add_action('wp_head', 'custom_social_meta_tags');

function custom_social_meta_tags()
{
  if (!is_single()) return;

  global $post;

  $title = get_the_title($post);
  $description = wp_strip_all_tags($post->post_content);
  $description = mb_substr($description, 0, 160); // Shorten to 160 chars
  $description = esc_attr($description);

  $image = '';
  if (has_post_thumbnail($post)) {
    $medium = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium');
    $full   = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
    $image  = $medium ? $medium[0] : ($full ? $full[0] : '');
  }

  $url = get_permalink($post);
?>
  <!-- Haysky Social Sharing Meta Tags -->
  <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
  <meta property="og:description" content="<?php echo $description; ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="<?php echo esc_url($url); ?>" />
  <?php if ($image): ?>
    <meta property="og:image" content="<?php echo esc_url($image); ?>" />
  <?php endif; ?>

  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
  <meta name="twitter:description" content="<?php echo $description; ?>" />
  <?php if ($image): ?>
    <meta name="twitter:image" content="<?php echo esc_url($image); ?>" />
  <?php endif; ?>
  <!-- Haysky Social Sharing Meta Tags -->
<?php
}
