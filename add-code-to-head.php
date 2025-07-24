<?php
// Add HTML to <head>
function haysky_insert_custom_head_html()
{
  $custom_html = get_option('haysky_add_code_to_head', '');
  if (!empty($custom_html)) {
    echo "\n<!-- Custom Head HTML Start -->\n";
    echo $custom_html . "\n";
    echo "<!-- Custom Head HTML End -->\n";
  }
}
add_action('wp_head', 'haysky_insert_custom_head_html');
