<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

add_action( 'admin_menu', 'emwa_add_admin_menu' );
add_action( 'admin_init', 'emwa_settings_init' );

// Add the options menu page
function emwa_add_admin_menu(  ) { 
	add_submenu_page( 'themes.php', 'Editor Access', 'Editor Access', 'manage_options', 'editorMenuWidgetAccess', 'emwa_options_page' );
}

// Options sections
function emwa_settings_init(  ) { 

	register_setting( 'emwaPage', 'emwa_settings' );

	add_settings_section(
		'emwaPage_section', 
		__( 'Hide Appearance Menus', 'editor-menu-and-widget-access' ), 
		'emwa_settings_section_callback', 
		'emwaPage'
	);

	add_settings_field( 
		'emwa_chk_themes', 
		__( 'Themes', 'editor-menu-and-widget-access' ), 
		'emwa_chk_themes_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

	add_settings_field( 
		'emwa_chk_custom', 
		__( 'Customize', 'editor-menu-and-widget-access' ), 
		'emwa_chk_custom_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

	add_settings_field( 
		'emwa_chk_widgets', 
		__( 'Widgets', 'editor-menu-and-widget-access' ), 
		'emwa_chk_widgets_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

	add_settings_field( 
		'emwa_chk_menus', 
		__( 'Menus', 'editor-menu-and-widget-access' ), 
		'emwa_chk_menus_render', 
		'emwaPage', 
		'emwaPage_section' 
	);


// Other menus tab

	//register_setting( 'emwaOtherMenus', 'emwaOtherMenus' );

  add_settings_section(
      'emwaOtherMenus_section',
      __('Hide Other Menus', 'editor-menu-and-widget-access'),
      'emwaOtherMenus_section_cb',
      'emwaPage'
  );

  add_settings_field(
      'emwa_field_menus',
      __('Other Menus', 'editor-menu-and-widget-access'),
      'emwa_field_menus_cb',
      'emwaPage',
      'emwaOtherMenus_section'
  );

	// Admin Bar tab

	register_setting( 'emwaAdminbar', 'emwaAdminbar' );

  add_settings_section(
      'emwaAdminbar_section',
      __('Hide Admin Bar Items', 'editor-menu-and-widget-access'),
      'emwaAdminbar_section_cb',
      'emwaAdminbar'
  );

  add_settings_field(
      'emwa_adminbar_menus',
      __('Admin Bar Items', 'editor-menu-and-widget-access'),
      'emwa_adminbar_menus_cb',
      'emwaAdminbar',
      'emwaAdminbar_section'
  );

	// Help tab

	register_setting( 'emwaHelp', 'emwaHelp' );

  add_settings_section(
      'emwaHelp_section',
      '',
      'emwaHelp_section_cb',
      'emwaHelp'
  );

}


// Add Settings link to Plugins page

function emwa_action_links( $links ) {

   $emwa_links[] = '<a href="'. esc_url( get_admin_url(null, 'themes.php?page=editorMenuWidgetAccess') ) .'">'. __( 'Settings', 'editor-menu-and-widget-access' ) .'</a>';

   return array_merge( $emwa_links, $links );

}



?>