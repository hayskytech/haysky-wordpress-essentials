<?php

add_filter('wp_revisions_to_keep', 'haysky_limit_post_revisions', 10, 2);

function haysky_limit_post_revisions($num, $post)
{
  $count = get_option('haysky_keep_revisions', '');

  if ($count === '0' || $count === 0) {
    return 0; // Disable revisions completely
  }

  if (is_numeric($count) && intval($count) > 0) {
    return intval($count); // Return the number of revisions to keep
  }

  return PHP_INT_MAX; // Allow unlimited revisions if no valid count is set
}
