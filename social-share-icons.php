<?php
add_action('wp_enqueue_scripts', 'custom_share_icons_enqueue');
function custom_share_icons_enqueue()
{
  wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
}

// Helper: generate social share icons HTML
function haysky_get_share_icons_html()
{
  $post_url   = urlencode(get_permalink());
  $post_title = urlencode(get_the_title());

  $icons = '<div class="custom-social-icons">';
  $icons .= '<strong style="display:block; margin-bottom:16px; font-size:16px; color:#333;">Share this post:</strong>';
  $icons .= '<div class="social-icons-row">';
  $icons .= '<a href="https://wa.me/?text=*' . $post_title . '*%20%0A%0A' . $post_url . '" target="_blank" class="social-icon whatsapp" title="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>';
  $icons .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $post_url . '" target="_blank" class="social-icon facebook" title="Share on Facebook"><i class="fab fa-facebook-f"></i></a>';
  $icons .= '<a href="https://twitter.com/intent/tweet?url=' . $post_url . '&text=' . $post_title . '" target="_blank" class="social-icon twitter" title="Share on Twitter"><i class="fab fa-x-twitter"></i></a>';
  $icons .= '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $post_url . '" target="_blank" class="social-icon linkedin" title="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>';
  $icons .= '<a href="mailto:?subject=' . $post_title . '&body=' . $post_url . '" class="social-icon email" title="Share via Email"><i class="fas fa-envelope"></i></a>';
  $icons .= '</div>';
  $icons .= '</div>';
  return $icons;
}

// Helper: shared CSS (output once)
function haysky_get_share_icons_style()
{
  static $printed = false;
  if ($printed) return '';
  $printed = true;

  return '
  <style>
    .custom-social-icons {
      width: 100%;
      box-sizing: border-box;
      padding: 10px 0;
    }
    .custom-social-icons .social-icons-row {
      display: flex;
      gap: 18px;
      align-items: center;
      justify-content: flex-start;
      margin-bottom: 0;
    }
    .custom-social-icons .social-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: #f7f7f7;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      font-size: 22px;
      color: #fff;
      text-decoration: none;
      transition: background 0.3s, color 0.3s, box-shadow 0.3s;
      border: 1px solid #e0e0e0;
    }
    .social-icon.whatsapp { color: #fff; background: #25D366; border-color: #25D366; }
    .social-icon.facebook { color: #fff; background: #3b5998; border-color: #3b5998; }
    .social-icon.twitter { color: #fff; background: #1da1f2; border-color: #1da1f2; }
    .social-icon.linkedin { color: #fff; background: #0077b5; border-color: #0077b5; }
    .social-icon.email { color: #fff; background: #ea4335; border-color: #ea4335; }
    .custom-social-icons .social-icon:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.12);
      filter: brightness(0.85);
    }
    .custom-social-icons .social-icon i {
      pointer-events: none;
    }
  </style>';
}

// Display after title / before featured image (via post_thumbnail_html filter)
add_filter('post_thumbnail_html', 'haysky_share_icons_before_thumbnail', 10, 5);
function haysky_share_icons_before_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr)
{
  static $done = false;
  if ($done) return $html;

  if (!get_option('haysky_social_share_icons_before', false)) {
    return $html;
  }

  // Only on single post pages, and only for the main queried post
  $queried = get_queried_object();
  if (!is_single() || !$queried || $post_id !== $queried->ID) {
    return $html;
  }

  $done = true;
  $icons = '<div style="border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px;">'
    . haysky_get_share_icons_html() . '</div>' . haysky_get_share_icons_style();
  return $icons . $html;
}

// Display after content
add_filter('the_content', 'haysky_share_icons_after_content');
function haysky_share_icons_after_content($content)
{
  if (!get_option('haysky_social_share_icons', false)) {
    return $content;
  }
  if (is_single() && in_the_loop() && is_main_query()) {
    $after_html = '<div style="border-top: 1px solid #ddd; margin-top: 30px; padding-top: 10px;">'
      . haysky_get_share_icons_html() . '</div>' . haysky_get_share_icons_style();
    return $content . $after_html;
  }
  return $content;
}
