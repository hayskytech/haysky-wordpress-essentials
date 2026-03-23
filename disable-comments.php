<?php

// Disable comments plugin code by Haysky
// Only disable comments if the option is enabled
if (get_option('haysky_disable_comments', 0)) {

  add_action('init', function () {
    // Disable comment support for all post types
    foreach (get_post_types() as $post_type) {
      if (post_type_supports($post_type, 'comments')) {
        remove_post_type_support($post_type, 'comments');
        remove_post_type_support($post_type, 'trackbacks');
      }
    }
  });

  // Close comments on the front-end
  add_filter('comments_open', '__return_false', 20, 2);
  add_filter('pings_open', '__return_false', 20, 2);

  // Hide existing comments
  add_filter('comments_array', '__return_empty_array', 10, 2);

  // Remove comments page in menu
  add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
  });

  // Redirect any user trying to access comments page
  add_action('admin_init', function () {
    global $pagenow;
    if ($pagenow === 'edit-comments.php' || $pagenow === 'comment.php') {
      wp_redirect(admin_url());
      exit;
    }
  });

  // Remove comments metabox from dashboard
  add_action('wp_dashboard_setup', function () {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
  });

  // Remove comments link from admin bar
  add_action('admin_bar_menu', function ($wp_admin_bar) {
    $wp_admin_bar->remove_node('comments');
  }, 999);
}
