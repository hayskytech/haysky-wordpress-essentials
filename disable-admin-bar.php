<?php
add_action('init', function (){
  if(get_option('haysky_disable_admin_bar', false)) {
    add_filter('show_admin_bar', '__return_false');
  }
});