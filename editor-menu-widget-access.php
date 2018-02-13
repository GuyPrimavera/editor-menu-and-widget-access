<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: https://designbymito.com/
 * Description: Allow Editors access to the Menus and Widgets areas of WordPress admin's Appearance menu, whilst hiding the theme selection page.
 * Author: Guy Primavera
 * Author URI: http://www.guyprimavera.com/
 * Version: 2.0
 * Text Domain: editor-menu-widget-access
 * License: GPL2
 *
 * Copyright 2016 Guy Primavera
 */

if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

include ('admin/caps.php');
include ('admin/menus.php');
include ('admin/options.php');


?>