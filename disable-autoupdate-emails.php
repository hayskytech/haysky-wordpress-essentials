<?php
if (get_option('haysky_disable_autoupdate_emails', 0)) {

  // Disable core update notification emails
  add_filter( 'auto_core_update_send_email', '__return_false' );

  // Disable plugin update notification emails
  add_filter( 'auto_plugin_update_send_email', '__return_false' );

  // Disable theme update notification emails
  add_filter( 'auto_theme_update_send_email', '__return_false' );

}