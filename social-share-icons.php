<?php
add_action('wp_enqueue_scripts', 'custom_share_icons_enqueue');
function custom_share_icons_enqueue()
{
  wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
}

add_filter('the_content', 'add_social_share_icons_with_icons');
function add_social_share_icons_with_icons($content)
{
  if (!get_option('haysky_social_share_icons', false)) {
    return $content; // Return content unchanged if option is not enabled
  }
  if (is_single() && in_the_loop() && is_main_query()) {
    $post_url   = urlencode(get_permalink());
    $post_title = urlencode(get_the_title());


    $icons = '<div class="custom-social-icons" style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd;">';
    $icons .= '<strong style="display:block; margin-bottom:16px; font-size:16px; color:#333;">Share this post:</strong>';
    $icons .= '<div class="social-icons-row">';
    $icons .= '<a href="https://wa.me/?text=' . $post_title . '%20' . $post_url . '" target="_blank" class="social-icon whatsapp" title="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>';
    $icons .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $post_url . '" target="_blank" class="social-icon facebook" title="Share on Facebook"><i class="fab fa-facebook-f"></i></a>';
    $icons .= '<a href="https://twitter.com/intent/tweet?url=' . $post_url . '&text=' . $post_title . '" target="_blank" class="social-icon twitter" title="Share on Twitter"><i class="fab fa-x-twitter"></i></a>';
    $icons .= '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $post_url . '" target="_blank" class="social-icon linkedin" title="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>';
    $icons .= '<a href="mailto:?subject=' . $post_title . '&body=' . $post_url . '" class="social-icon email" title="Share via Email"><i class="fas fa-envelope"></i></a>';
    $icons .= '</div>';
    $icons .= '</div>';

    // Improved inline CSS for colorful icons and perfect alignment
    $icons .= '
    <style>
      .custom-social-icons {
        width: 100%;
        box-sizing: border-box;
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
        background: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        filter: brightness(0.85);
      }
      .custom-social-icons .social-icon i {
        pointer-events: none;
      }
    </style>';

    return $content . $icons;
  }

  return $content;
}
