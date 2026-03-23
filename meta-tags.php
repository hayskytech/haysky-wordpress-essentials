<?php
add_action('wp_head', 'custom_social_meta_tags', 5); // Priority 5 to appear earlier in head

function custom_social_meta_tags()
{
  // Check if meta tags are enabled in settings
  if (!get_option('haysky_meta_tags')) {
    return;
  }

  $title        = '';
  $description  = '';
  $image        = '';
  $image_width  = '';
  $image_height = '';
  $url          = '';
  $type         = 'website';
  $site_name    = get_bloginfo('name');
  $locale       = get_locale();

  if (is_singular()) {
    $post        = get_queried_object();
    $title       = get_the_title($post);
    $description = get_the_excerpt($post);
    if (!$description) {
      $description = $post->post_content;
    }
    $description = wp_strip_all_tags($description);
    $url         = get_permalink($post);
    $type        = 'article';

    if (has_post_thumbnail($post)) {
      $image_data = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
      if (!$image_data) {
        $image_data = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
      }
      if ($image_data) {
        $image        = $image_data[0];
        $image_width  = $image_data[1];
        $image_height = $image_data[2];
      }
    }
  } elseif (is_archive()) {
    if (is_category()) {
      $term        = get_queried_object();
      $title       = single_cat_title('', false);
      $description = wp_strip_all_tags(category_description($term->term_id));
      $url         = get_category_link($term->term_id);
    } elseif (is_tag()) {
      $term        = get_queried_object();
      $title       = single_tag_title('', false);
      $description = wp_strip_all_tags(tag_description($term->term_id));
      $url         = get_tag_link($term->term_id);
    } elseif (is_tax()) {
      $term        = get_queried_object();
      $title       = single_term_title('', false);
      $description = wp_strip_all_tags(term_description($term->term_id, $term->taxonomy));
      $url         = get_term_link($term);
    } elseif (is_author()) {
      $author      = get_queried_object();
      $title       = get_the_author_meta('display_name', $author->ID);
      $description = get_the_author_meta('description', $author->ID);
      $url         = get_author_posts_url($author->ID);
    } else {
      $title       = get_bloginfo('name');
      $description = get_bloginfo('description');
      $url         = home_url('/');
    }
  } else {
    $title       = get_bloginfo('name');
    $description = get_bloginfo('description');
    $url         = home_url('/');
  }

  // Fallback to Site Logo if no post image
  if (empty($image)) {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
      $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
      if ($logo_data) {
        $image        = $logo_data[0];
        $image_width  = $logo_data[1];
        $image_height = $logo_data[2];
      }
    }
  }

  $description = mb_substr(trim($description), 0, 160);
  $description = esc_attr($description);
  $title       = esc_attr($title);
  $twitter_card = !empty($image) ? 'summary_large_image' : 'summary';
?>
  <!-- Haysky Social Sharing Meta Tags -->
  <meta property="og:locale" content="<?php echo esc_attr($locale); ?>" />
  <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>" />
  <meta property="og:title" content="<?php echo $title; ?>" />
  <meta property="og:description" content="<?php echo $description; ?>" />
  <meta property="og:type" content="<?php echo $type; ?>" />
  <meta property="og:url" content="<?php echo esc_url($url); ?>" />
  <?php if ($image): ?>
    <meta property="og:image" content="<?php echo esc_url($image); ?>" />
    <meta property="og:image:secure_url" content="<?php echo esc_url($image); ?>" />
    <?php if ($image_width && $image_height): ?>
      <meta property="og:image:width" content="<?php echo absint($image_width); ?>" />
      <meta property="og:image:height" content="<?php echo absint($image_height); ?>" />
    <?php endif; ?>
  <?php endif; ?>

  <meta name="twitter:card" content="<?php echo esc_attr($twitter_card); ?>" />
  <meta name="twitter:title" content="<?php echo $title; ?>" />
  <meta name="twitter:description" content="<?php echo $description; ?>" />
  <?php if ($image): ?>
    <meta name="twitter:image" content="<?php echo esc_url($image); ?>" />
  <?php endif; ?>
  <!-- Haysky Social Sharing Meta Tags -->
<?php
}
