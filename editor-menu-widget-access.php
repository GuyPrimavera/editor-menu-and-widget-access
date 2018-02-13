<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: https://wordpress.org/plugins/editor-menu-and-widget-access/
 * Description: Allow and control Editors' access to the Menus, Widgets and Customize areas of the Appearance menu. You can also hide custom theme options.
 * Author: Guy Primavera
 * Author URI: http://guyprimavera.com/
 * Version: 2.3.1
 * Text Domain: editor-menu-widget-access
 * License: GPL2
 *
 */

if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

include ('admin/caps.php');
include ('admin/menus.php');
include ('admin/options.php');


?>