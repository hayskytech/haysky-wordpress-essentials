<?php
add_action('init', 'enable_classic_editor');
function enable_classic_editor()
{
  // Enable Classic Editor
  if (get_option('haysky_classic_editor', false)) {
    add_filter('use_block_editor_for_post', '__return_false', 100);
    add_filter('use_block_editor_for_post_type', '__return_false', 100);
  }
}


add_action('init', 'enable_classic_widgets');
function enable_classic_widgets()
{
  // Enable Classic Widgets
  if (get_option('haysky_classic_widgets', false)) {
    add_filter('use_widgets_block_editor', '__return_false');
    add_filter('gutenberg_use_widgets_block_editor', '__return_false');
  }
}
