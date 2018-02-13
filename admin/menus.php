<?php 
if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

function emwa_new_admin_menu() {

	$user = new WP_User(get_current_user_id());

	if (!empty( $user->roles) && is_array($user->roles)) {
		foreach ($user->roles as $role)
		$role = $role;
	}

	if($role == "editor") {

		$emwaOptions = get_option( 'emwa_settings' );
		global $submenu;

		/*
		// Themes Submenu
		if ( isset ( $emwaOptions['emwa_chk_themes'] ) ) { 
			//remove_submenu_page( 'themes.php', 'themes.php' );
			unset($submenu['themes.php'][5]);		
		}
		*/
		unset($submenu['themes.php'][5]);		

		// Customize Submenu
		if ( isset ( $emwaOptions['emwa_chk_custom'] ) ) { 
			unset($submenu['themes.php'][6]);		
			unset($submenu['themes.php'][15]);		
			unset($submenu['themes.php'][20]);		
		}

		// Widgets Submenu
		if ( isset ( $emwaOptions['emwa_chk_widgets'] ) ) { 
			remove_submenu_page( 'themes.php', 'widgets.php' );
			//unset($submenu['themes.php'][6]);		
		}

		// Menus Submenu
		if ( isset ( $emwaOptions['emwa_chk_menus'] ) ) { 
			unset($submenu['themes.php'][10]);		
		}

		/*
		// Header Submenu
		if ( isset ( $emwaOptions['emwa_chk_header'] ) ) { 
			unset($submenu['themes.php'][15]);		
		}

		// Background Submenu
		if ( isset ( $emwaOptions['emwa_chk_background'] ) ) { 
			unset($submenu['themes.php'][20]);		
		}
		*/

		// Hide Appearance Menu if all submenus are set to "hidden"
		if ( 
		( isset ( $emwaOptions['emwa_chk_custom'] ) ) && 
		( isset ( $emwaOptions['emwa_chk_widgets'] ) ) && 
		( isset ( $emwaOptions['emwa_chk_menus'] ) )  
		) {
			remove_menu_page( 'themes.php' );
		} 

	}
}

add_action('admin_init', 'emwa_new_admin_menu');


function emwa_adminbar_link() {

	$user = new WP_User(get_current_user_id());

	if (!empty( $user->roles) && is_array($user->roles)) {
		foreach ($user->roles as $role)
		$role = $role;
	}

	if($role == "editor") {

		$emwaOptions = get_option( 'emwa_settings' );
    global $wp_admin_bar;

	    $wp_admin_bar->remove_menu('themes');

    if ( isset ( $emwaOptions['emwa_chk_custom'] ) ) { 
	    $wp_admin_bar->remove_menu('customize');
	  }
    if ( isset ( $emwaOptions['emwa_chk_widgets'] ) ) { 
	    $wp_admin_bar->remove_menu('widgets');
	  }
    if ( isset ( $emwaOptions['emwa_chk_menus'] ) ) { 
	    $wp_admin_bar->remove_menu('menus');
	  }

  }
		
}
add_action( 'wp_before_admin_bar_render', 'emwa_adminbar_link', 999 );


function emwa_hide_custom() {

	$user = new WP_User(get_current_user_id());

	if (!empty( $user->roles) && is_array($user->roles)) {
		foreach ($user->roles as $role)
		$role = $role;
	}

	$emwaOptions = get_option( 'emwa_settings' );

	if( ($role == "editor") && ( isset ( $emwaOptions['emwa_chk_custom'] ) ) ) {

		echo "<style type='text/css' media='screen'>
			.hide-if-no-customize { display: none!important; }
		</style>
		";
	}
}
	 
add_action( 'admin_head', 'emwa_hide_custom', 99 ); 




?>