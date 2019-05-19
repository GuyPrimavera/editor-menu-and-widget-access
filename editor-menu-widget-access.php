<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: https://wordpress.org/plugins/editor-menu-and-widget-access/
 * Description: Allow and control Editors' access to the Menus, Widgets and Customize areas of the Appearance menu. You can also hide custom theme options.
 * Author: Guy Primavera
 * Author URI: https://guyprimavera.com/
 * Version: 3.0.2
 * Text Domain: editor-menu-and-widget-access
 * Domain Path: /lang
 * License: GPL3
 *
 */

if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

include ('admin/caps.php');
include ('admin/menus.php');
include ('admin/adminbar.php');
include ('options/options.php');

// Plugin action links
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'emwa_action_links' );

/// i18n

function emwa_i18n_setup() {
  $locale = apply_filters( 'plugin_locale', get_locale(), 'editor-menu-and-widget-access' );
  load_textdomain( 'editor-menu-and-widget-access', WP_LANG_DIR . "/$locale.mo" );
	load_plugin_textdomain( 'editor-menu-and-widget-access', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'emwa_i18n_setup' );


?>