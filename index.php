<?php

/**
 * Plugin Name: Haysky WordPress Essentials
 * Description: Adds social share icons, meta tags. Enable classic editor & widgets. Track post views and limit revisions. Add custom HTML to head.
 * Version: 1.0.0
 * Author: Haysky
 * Author URI: https://haysky.com
 * License: GPL2
 */

# Exit if accessed directly
if (!defined("ABSPATH")) {
  exit;
}

// Register settings
function haysky_settings()
{
  register_setting('haysky_settings_group', 'haysky_add_code_to_head');
  register_setting('haysky_settings_group', 'haysky_social_share_icons');
  register_setting('haysky_settings_group', 'haysky_social_share_icons_before');
  register_setting('haysky_settings_group', 'haysky_post_views');
  register_setting('haysky_settings_group', 'haysky_meta_tags');
  register_setting('haysky_settings_group', 'haysky_classic_editor');
  register_setting('haysky_settings_group', 'haysky_classic_widgets');
  register_setting('haysky_settings_group', 'haysky_keep_revisions');
  register_setting('haysky_settings_group', 'haysky_disable_admin_bar');
  register_setting('haysky_settings_group', 'haysky_resize_uploaded_images');
  register_setting('haysky_settings_group', 'haysky_disable_comments');
  register_setting('haysky_settings_group', 'haysky_disable_autoupdate_emails');
  register_setting('haysky_settings_group', 'haysky_show_active_sessions');
}
add_action('admin_init', 'haysky_settings');

// Add settings page
function haysky_settings_menu()
{
  add_options_page(
    'Haysky Settings',
    'Haysky Settings',
    'manage_options',
    'haysky_settings',
    'haysky_settings_page'
  );
}
add_action('admin_menu', 'haysky_settings_menu');

include_once plugin_dir_path(__FILE__) . 'post-views-tracker.php';
include_once plugin_dir_path(__FILE__) . 'dashboard-widget-post-views.php';
register_activation_hook(__FILE__, 'pvt_create_tables');

include_once plugin_dir_path(__FILE__) . 'add-code-to-head.php';
include_once plugin_dir_path(__FILE__) . 'social-share-icons.php';
include_once plugin_dir_path(__FILE__) . 'meta-tags.php';
include_once plugin_dir_path(__FILE__) . 'classic.php';
include_once plugin_dir_path(__FILE__) . 'limit_revisions.php';
include_once plugin_dir_path(__FILE__) . 'disable-admin-bar.php';
include_once plugin_dir_path(__FILE__) . 'resize-uploaded-images.php';
include_once plugin_dir_path(__FILE__) . 'disable-comments.php';
include_once plugin_dir_path(__FILE__) . 'disable-autoupdate-emails.php';
include_once plugin_dir_path(__FILE__) . 'active-sessions.php';

// Settings page content
function haysky_settings_page()
{
?>
  <div class="wrap">
    <h1>Custom Head HTML</h1>
    <form method="post" action="options.php">
      <?php
      settings_fields('haysky_settings_group');
      do_settings_sections('haysky_settings_group');
      $code = esc_textarea(get_option('haysky_add_code_to_head', ''));
      ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">HTML to insert into &lt;head&gt;</th>
          <td>
            <textarea name="haysky_add_code_to_head" rows="7" cols="50"><?php echo $code; ?></textarea>
            <p class="description">You can add meta tags, styles, links etc. Do not include &lt;head&gt; tag itself.</p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Social share icons</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_social_share_icons" value="1" <?php checked(1, get_option('haysky_social_share_icons', 0)); ?> />
              Display after content
            </label>
            <br>
            <label>
              <input type="checkbox" name="haysky_social_share_icons_before" value="1" <?php checked(1, get_option('haysky_social_share_icons_before', 0)); ?> />
              Display after title
            </label>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Track post views</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_post_views" value="1" <?php checked(1, get_option('haysky_post_views', 0)); ?> />
              Enable
            </label>
            <p class="description">Enable to track and display post view counts.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Add meta tags</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_meta_tags" value="1" <?php checked(1, get_option('haysky_meta_tags', 0)); ?> />
              Enable
            </label>
            <p class="description">Enable to add meta tags for social sharing link and image preview.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Use Classic Editor</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_classic_editor" value="1" <?php checked(1, get_option('haysky_classic_editor', 0)); ?> />
              Enable
            </label>
            <p class="description">Enable to use the classic editor instead of Gutenberg.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Use Classic Widgets</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_classic_widgets" value="1" <?php checked(1, get_option('haysky_classic_widgets', 0)); ?> />
              Enable
            </label>
            <p class="description">Enable to use classic widgets instead of block-based widgets.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Limit number of post revisions</th>
          <td>
            <label>
              <input type="number" name="haysky_keep_revisions" min="0" step="1"
                value="<?php echo get_option('haysky_keep_revisions', ''); ?>" />
            </label>
            <p class="description">Leave empty to allow unlimited revisions</p>
            <p class="description">Enter 3 to allow three revisions per post.</p>
            <p class="description">0 to disable completely. </p>
          </td>
        </tr>
        <tr>
          <th scope="row">Disable Admin Bar in front-end</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_disable_admin_bar" value="1" <?php checked(1, get_option('haysky_disable_admin_bar', 0)); ?> />
              Disable
            </label>
            <p class="description">Disable the admin bar for all users.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Resize uploaded images</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_resize_uploaded_images" value="1" <?php checked(1, get_option('haysky_resize_uploaded_images', 0)); ?> />
              Enable
            </label>
            <p class="description">Automatically resize uploaded images to a maximum width of 1500px and compress them.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Disable Comments</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_disable_comments" value="1" <?php checked(1, get_option('haysky_disable_comments', 0)); ?> />
              Disable
            </label>
            <p class="description">Disable comments across the site.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Disable Auto-update Emails</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_disable_autoupdate_emails" value="1" <?php checked(1, get_option('haysky_disable_autoupdate_emails', 0)); ?> />
              Disable
            </label>
            <p class="description">Disable email notifications for automatic updates.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Show Active Sessions</th>
          <td>
            <label>
              <input type="checkbox" name="haysky_show_active_sessions" value="1" <?php checked(1, get_option('haysky_show_active_sessions', 0)); ?> />
              Enable
            </label>
            <p class="description">Show active login sessions on user profile pages. Admins can view and revoke sessions for any user.</p>
          </td>
        </tr>
      </table>
      <?php submit_button(); ?>
    </form>
  </div>
<?php
}


add_action('init', function () {
  if (isset($_GET['action']) && $_GET['action'] === 'mk_file_folder_manager') {
    wp_die('Blocked', '403 Forbidden', ['response' => 403]);
  }
});

add_shortcode('site-title', function () {
  return get_bloginfo('name');
});
