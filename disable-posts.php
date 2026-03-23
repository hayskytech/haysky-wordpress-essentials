<?php

if (!defined('ABSPATH')) {
  exit;
}

if (!get_option('haysky_disable_posts')) {
  return;
}

// Remove Posts from admin menu
add_action('admin_menu', function () {
  remove_menu_page('edit.php');
});

// Remove Posts from admin bar
add_action('admin_bar_menu', function ($wp_admin_bar) {
  $wp_admin_bar->remove_node('new-post');
}, 999);

// Redirect direct access to posts admin pages
add_action('current_screen', function ($screen) {
  if (in_array($screen->post_type, ['post']) && in_array($screen->base, ['edit', 'post'])) {
    wp_redirect(admin_url());
    exit;
  }
});

// Unregister the post type on init
add_action('init', function () {
  global $wp_post_types;
  if (isset($wp_post_types['post'])) {
    unset($wp_post_types['post']);
  }
}, 20);
