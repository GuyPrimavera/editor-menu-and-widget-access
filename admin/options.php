<?php
if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

add_action( 'admin_menu', 'emwa_add_admin_menu' );
add_action( 'admin_init', 'emwa_settings_init' );


function emwa_add_admin_menu(  ) { 

	add_submenu_page( 'themes.php', 'Editor Access', 'Editor Access', 'manage_options', 'editorMenuWidgetAccess', 'emwa_options_page' );

}


function emwa_settings_init(  ) { 

	register_setting( 'emPage', 'emwa_settings' );

	add_settings_section(
		'emwa_emPage_section', 
		__( '<i class="dashicons-before dashicons-admin-appearance"></i> Appearance Menu Settings', 'emwa-admin-theme' ), 
		'emwa_settings_section_callback', 
		'emPage'
	);

	add_settings_field( 
		'emwa_chk_themes', 
		__( 'Themes', 'emwa-admin-theme' ), 
		'emwa_chk_themes_render', 
		'emPage', 
		'emwa_emPage_section' 
	);

	add_settings_field( 
		'emwa_chk_custom', 
		__( 'Customize', 'emwa-admin-theme' ), 
		'emwa_chk_custom_render', 
		'emPage', 
		'emwa_emPage_section' 
	);

	add_settings_field( 
		'emwa_chk_widgets', 
		__( 'Widgets', 'emwa-admin-theme' ), 
		'emwa_chk_widgets_render', 
		'emPage', 
		'emwa_emPage_section' 
	);

	add_settings_field( 
		'emwa_chk_menus', 
		__( 'Menus', 'emwa-admin-theme' ), 
		'emwa_chk_menus_render', 
		'emPage', 
		'emwa_emPage_section' 
	);

/*
	add_settings_field( 
		'emwa_chk_header', 
		__( 'Header', 'emwa-admin-theme' ), 
		'emwa_chk_header_render', 
		'emPage', 
		'emwa_emPage_section' 
	);

	add_settings_field( 
		'emwa_chk_background', 
		__( 'Background', 'emwa-admin-theme' ), 
		'emwa_chk_background_render', 
		'emPage', 
		'emwa_emPage_section' 
	);
*/


}


function emwa_chk_themes_render(  ) { 

	$options = get_option( 'emwa_settings' );
	if ( isset ( $options['emwa_chk_themes'] ) ) { $emwaThemes = $options['emwa_chk_themes'];
	} else { $emwaThemes = 0; };

	?>
	<input type='checkbox' name='emwa_settings[emwa_chk_themes]' <?php checked( $emwaThemes, 1 ); ?> value='1' checked disabled>
	<em>Always hidden</em>
	<?php

}


function emwa_chk_custom_render(  ) { 

	$options = get_option( 'emwa_settings' );
	if ( isset ( $options['emwa_chk_custom'] ) ) { $emwaCustom = $options['emwa_chk_custom'];
	} else { $emwaCustom = 0; };

	?>
	<input type='checkbox' name='emwa_settings[emwa_chk_custom]' <?php checked( $emwaCustom, 1 ); ?> value='1'>
	<em>Including "Header" and "Background".</em>
	<?php

}


function emwa_chk_widgets_render(  ) { 

	$options = get_option( 'emwa_settings' );
	if ( isset ( $options['emwa_chk_widgets'] ) ) { $emwaWidgets = $options['emwa_chk_widgets'];
	} else { $emwaWidgets = 0; };

	?>
	<input type='checkbox' name='emwa_settings[emwa_chk_widgets]' <?php checked( $emwaWidgets, 1 ); ?> value='1'>
	<?php

}


function emwa_chk_menus_render(  ) { 

	$options = get_option( 'emwa_settings' );
	if ( isset ( $options['emwa_chk_menus'] ) ) { $emwaMenus = $options['emwa_chk_menus'];
	} else { $emwaMenus = 0; };

	?>
	<input type='checkbox' name='emwa_settings[emwa_chk_menus]' <?php checked( $emwaMenus, 1 ); ?> value='1'>
	<?php

}

/*
function emwa_chk_header_render(  ) { 

	$options = get_option( 'emwa_settings' );
	if ( isset ( $options['emwa_chk_header'] ) ) { $emwaHeader = $options['emwa_chk_header'];
	} else { $emwaHeader = 0; };

	?>
	<input type='checkbox' name='emwa_settings[emwa_chk_header]' <?php checked( $emwaHeader, 1 ); ?> value='1'>
	<?php

}


function emwa_chk_background_render(  ) { 

	$options = get_option( 'emwa_settings' );
	if ( isset ( $options['emwa_chk_background'] ) ) { $emwaBackground = $options['emwa_chk_background'];
	} else { $emwaBackground = 0; };

	?>
	<input type='checkbox' name='emwa_settings[emwa_chk_background]' <?php checked( $emwaBackground, 1 ); ?> value='1'>
	<?php

}
*/


function emwa_settings_section_callback(  ) { 

	echo __( 'Select the items to <strong>hide from Editors</strong> in the <em>Appearance</em> menu:', 'emwa-admin-theme' );

}


function emwa_options_page(  ) { 

	?>
	<div class="wrap">
		<form action='options.php' method='post'>

			<h2>Editor Menu and Widget Access</h2>

			<?php
			settings_fields( 'emPage' );
			do_settings_sections( 'emPage' );
			submit_button();
			?>

		</form>
	</div>
	<?php

}

// Thanks to jeroensormani for help with these settings.

?>