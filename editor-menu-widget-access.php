<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: http://www.guyprimavera.com/
 * Description: Allow Editors access to the Menus and Widgets areas of WordPress admin's Appearance menu, whilst hiding the theme selection page.
 * Author: Guy Primavera
 * Author URI: http://www.guyprimavera.com/
 * Version: 1.0
 * Text Domain: editor-menu-widget-access
 * License: GPL2
 *
 * Copyright 2015 Guy Primavera
 */


// Allow Editors access to the Appearance menu

function gp_allow_edit_theme_options( $caps ) {
	
	/* check if the user can edit_pages */
	if( ! empty( $caps[ 'edit_pages' ] ) ) {
		
		/* allow the user to edit theme options */
		$caps[ 'edit_theme_options' ] = true;
		
	}
	
	/* return the new capabilities */
	return $caps;
	
}

add_filter( 'user_has_cap', 'gp_allow_edit_theme_options' );

// Hide the other pages in the Appearance menu

function gp_new_admin_menu() {

$user = new WP_User(get_current_user_id());
if (!empty( $user->roles) && is_array($user->roles)) {
foreach ($user->roles as $role)
$role = $role;
}

if($role == "editor") {
remove_submenu_page( 'themes.php', 'themes.php' );
}
}

add_action('admin_init', 'gp_new_admin_menu');

?>