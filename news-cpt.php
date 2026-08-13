<?php

if (!defined('ABSPATH')) {
  exit;
}

if (!get_option('haysky_enable_news_cpt')) {
  return;
}

add_action('init', function () {
  register_post_type('news', [
    'labels' => [
      'name'               => 'News',
      'singular_name'      => 'News',
      'add_new'            => 'Add New',
      'add_new_item'       => 'Add New News',
      'edit_item'          => 'Edit News',
      'new_item'           => 'New News',
      'view_item'          => 'View News',
      'view_items'         => 'View News',
      'search_items'       => 'Search News',
      'not_found'          => 'No news found',
      'not_found_in_trash' => 'No news found in Trash',
      'all_items'          => 'All News',
      'menu_name'          => 'News',
    ],
    'public'             => true,
    'has_archive'        => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'show_in_nav_menus'  => true,
    'show_in_rest'       => true,
    'menu_icon'          => 'dashicons-newspaper',
    'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'author', 'custom-fields'],
    'taxonomies'         => ['category', 'post_tag'],
    'rewrite'            => ['slug' => 'news', 'with_front' => false],
  ]);
}, 0);
