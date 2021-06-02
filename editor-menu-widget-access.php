<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: https://wordpress.org/plugins/editor-menu-and-widget-access/
 * Description: Allow and control Editor and Shop Manager access to the menus, widgets and appearance menu, plus other menus and adminbar items.
 * Author: Guy Primavera
 * Author URI: https://guyprimavera.com/
 * Version: 3.1.2
 * WC requires at least: 3.0.0
 * WC tested up to: 5.3
 * Text Domain: editor-menu-and-widget-access
 * Domain Path: /lang
 * License: GPL3
 *
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

define( 'EMWA_PLUGIN_FILE', basename( __FILE__ ) );
define( 'EMWA_PLUGIN_FULL_PATH', __FILE__ );

include('admin/caps.php');
include('admin/menus.php');
include('admin/adminbar.php');
include('admin/roles.php');
include('options/options.php');

// Plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'emwa_action_links');
add_filter('plugin_row_meta', 'emwa_row_meta', 10, 2);    

/// i18n
function emwa_i18n_setup() {
  $locale = apply_filters('plugin_locale', get_locale(), 'editor-menu-and-widget-access');
  load_textdomain('editor-menu-and-widget-access', WP_LANG_DIR . "/$locale.mo");
  load_plugin_textdomain('editor-menu-and-widget-access', false, dirname(plugin_basename(__FILE__)) . '/lang/');
}
add_action('plugins_loaded', 'emwa_i18n_setup');

function emwa_deactivate() {
  $editor = get_role('editor');

  if (!empty($editor)) {
    $editor -> remove_cap('edit_theme_options');
  }
}
register_deactivation_hook(__FILE__, 'emwa_deactivate');
