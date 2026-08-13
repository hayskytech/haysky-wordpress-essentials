<?php

if (!defined('ABSPATH')) {
  exit;
}

if (!get_option('haysky_restrict_editor_pages')) {
  return;
}

add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
  if (!$user || !in_array('editor', (array) $user->roles)) {
    return $allcaps;
  }

  $page_caps = [
    'edit_pages',
    'edit_published_pages',
    'edit_others_pages',
    'edit_private_pages',
    'delete_pages',
    'delete_published_pages',
    'delete_others_pages',
    'delete_private_pages',
    'publish_pages',
  ];

  foreach ($page_caps as $cap) {
    $allcaps[$cap] = false;
  }

  return $allcaps;
}, 10, 4);

add_action('admin_menu', function () {
  if (!current_user_can('editor') || !in_array('editor', (array) wp_get_current_user()->roles)) {
    return;
  }
  remove_menu_page('edit.php?post_type=page');
});

add_action('admin_init', function () {
  if (!is_admin()) {
    return;
  }

  $user = wp_get_current_user();
  if (!in_array('editor', (array) $user->roles)) {
    return;
  }

  $pagenow = $GLOBALS['pagenow'] ?? '';
  $post_type = $_GET['post_type'] ?? '';
  $post_id = $_GET['post'] ?? $_POST['post_ID'] ?? 0;

  $is_page_screen = false;

  if (in_array($pagenow, ['post.php', 'post-new.php']) && $post_type === 'page') {
    $is_page_screen = true;
  }

  if ($pagenow === 'edit.php' && $post_type === 'page') {
    $is_page_screen = true;
  }

  if ($pagenow === 'post.php' && $post_id) {
    $post = get_post((int) $post_id);
    if ($post && $post->post_type === 'page') {
      $is_page_screen = true;
    }
  }

  if ($is_page_screen) {
    wp_die(
      __('You do not have permission to edit pages.', 'haysky-wordpress-essentials'),
      __('Access Denied', 'haysky-wordpress-essentials'),
      ['response' => 403, 'back_link' => true]
    );
  }
});
