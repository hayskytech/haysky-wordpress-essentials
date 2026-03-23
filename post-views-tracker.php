<?php

add_action('wp', 'pvt_track_post_views');
add_action('admin_init', 'pvt_add_admin_columns_support');

// Create custom tables on plugin activation
function pvt_create_tables()
{
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  $table_views = $wpdb->prefix . 'haysky_post_views';
  $table_daily = $wpdb->prefix . 'haysky_post_views_daily';

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $sql1 = "CREATE TABLE $table_views (
        post_id BIGINT(20) UNSIGNED NOT NULL,
        views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (post_id)
    ) $charset_collate;";

  $sql2 = "CREATE TABLE $table_daily (
        post_id BIGINT(20) UNSIGNED NOT NULL,
        view_date DATE NOT NULL,
        views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (post_id, view_date)
    ) $charset_collate;";

  dbDelta($sql1);
  dbDelta($sql2);
}

// Track views for single posts/pages
function pvt_track_post_views()
{
  if (!get_option('haysky_post_views', false)) {
    return; // Exit if post views tracking is disabled
  }
  if (!is_singular(array('post', 'page'))) return;

  global $post, $wpdb;
  $post_id = $post->ID;
  $table_views = $wpdb->prefix . 'haysky_post_views';
  $table_daily = $wpdb->prefix . 'haysky_post_views_daily';
  $today = current_time('Y-m-d');

  // Update total views
  $wpdb->query($wpdb->prepare("
        INSERT INTO $table_views (post_id, views)
        VALUES (%d, 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ", $post_id));

  // Update daily views
  $wpdb->query($wpdb->prepare("
        INSERT INTO $table_daily (post_id, view_date, views)
        VALUES (%d, %s, 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ", $post_id, $today));
}

// Show view counts in admin columns
function pvt_add_admin_columns_support()
{
  if (!get_option('haysky_post_views', false)) {
    return; // Exit if post views tracking is disabled
  }
  add_filter('manage_post_posts_columns', 'pvt_add_views_column');
  add_action('manage_post_posts_custom_column', 'pvt_render_views_column', 10, 2);

  add_filter('manage_page_posts_columns', 'pvt_add_views_column');
  add_action('manage_page_posts_custom_column', 'pvt_render_views_column', 10, 2);
}

function pvt_add_views_column($columns)
{
  $columns['pvt_views'] = 'Views';
  return $columns;
}

function pvt_render_views_column($column, $post_id)
{
  if ($column === 'pvt_views') {
    global $wpdb;
    $table = $wpdb->prefix . 'haysky_post_views';
    $views = $wpdb->get_var($wpdb->prepare("SELECT views FROM $table WHERE post_id = %d", $post_id));
    echo intval($views);
  }
}

// Add meta box in post editor
add_action('add_meta_boxes', 'pvt_add_post_views_meta_box');

function pvt_add_post_views_meta_box()
{
  add_meta_box(
    'pvt_post_views_meta_box',         // ID
    'Post Views',                      // Title
    'pvt_render_post_views_meta_box',  // Callback
    ['post', 'page'],                  // Post types
    'side',                            // Context
    'default'                          // Priority
  );
}

function pvt_render_post_views_meta_box($post)
{
  global $wpdb;
  $table = $wpdb->prefix . 'haysky_post_views';

  $views = $wpdb->get_var($wpdb->prepare(
    "SELECT views FROM $table WHERE post_id = %d",
    $post->ID
  ));

  echo '<p><strong>Total Views:</strong> ' . intval($views) . '</p>';
}
