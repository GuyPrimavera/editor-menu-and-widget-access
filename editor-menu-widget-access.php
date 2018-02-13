<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: http://www.guyprimavera.com/
 * Description: Allow Editors access to the Menus and Widgets areas of WordPress admin's Appearance menu, whilst hiding the theme selection page.
 * Author: Guy Primavera
 * Author URI: http://www.guyprimavera.com/
 * Version: 1.1
 * Text Domain: editor-menu-widget-access
 * License: GPL2
 *
 * Copyright 2016 Guy Primavera
 */


// Allow Editors access to the Appearance menu

function gp_allow_edit_theme_options( $caps ) {

	if( ! empty( $caps[ 'edit_pages' ] ) ) {
		$caps[ 'edit_theme_options' ] = true;
	}
	return $caps;
	
}

add_filter( 'user_has_cap', 'gp_allow_edit_theme_options' );

function gp_new_admin_menu() {

	$user = new WP_User(get_current_user_id());

	if (!empty( $user->roles) && is_array($user->roles)) {
		foreach ($user->roles as $role)
		$role = $role;
	}

	if($role == "editor") {
		remove_submenu_page( 'themes.php', 'themes.php' );
		global $submenu;
		//unset($submenu['themes.php'][6]);		
	}
}

add_action('admin_init', 'gp_new_admin_menu');


// Dashboard Widget

function add_emwa_widget() {
	
	$aqName = get_bloginfo('name');

	wp_add_dashboard_widget(
		'editor-access',         					// Widget slug.
		'Editor Menu and Widget Access',	// Title.
		'add_emwa_widget_function' 				// Display function.
	);
}

//add_action( 'wp_dashboard_setup', 'add_emwa_widget' );

function add_emwa_widget_function() {
	
	echo '	
	<p class="about-description">
		<h4>Allow <em>Editors</em> access to:</h4>
		<ul>
			<li><input type="checkbox" name="emwaMenu" /> Menus</li>
			<li><input type="checkbox" name="emwaWidg" /> Widgets</li>
			<li><input type="checkbox" name="emwaCust" /> Customize</li>
		</ul>
	</p>
	';
}

// Remove access to Themes page.

function gp_set_capabilities() {

    $editor = get_role( 'editor' );

    $caps = array(
        'edit_themes',
        'update_themes',
        'delete_themes',
        'install_themes',
        'upload_themes'
    );

    foreach ( $caps as $cap ) {
    
        // Remove the capability.
        $editor->remove_cap( $cap );
    }
}
add_action( 'init', 'gp_set_capabilities' );

?>