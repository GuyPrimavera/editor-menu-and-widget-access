<?php
if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

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
		__( '<i class="dashicons-before dashicons-admin-appearance"></i> Hide Appearance Menus', 'editor-menu-widget-access' ), 
		'emwa_settings_section_callback', 
		'emwaPage'
	);

	add_settings_field( 
		'emwa_chk_themes', 
		__( 'Themes', 'editor-menu-widget-access' ), 
		'emwa_chk_themes_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

	add_settings_field( 
		'emwa_chk_custom', 
		__( 'Customize', 'editor-menu-widget-access' ), 
		'emwa_chk_custom_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

	add_settings_field( 
		'emwa_chk_widgets', 
		__( 'Widgets', 'editor-menu-widget-access' ), 
		'emwa_chk_widgets_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

	add_settings_field( 
		'emwa_chk_menus', 
		__( 'Menus', 'editor-menu-widget-access' ), 
		'emwa_chk_menus_render', 
		'emwaPage', 
		'emwaPage_section' 
	);

// Other menus section
  add_settings_section(
      'emwaOther_section',
      __('<i class="dashicons-before dashicons-admin-post"></i> Hide Other Menus', 'editor-menu-widget-access'),
      'emwaOther_section_cb',
      'emwaPage'
  );

  add_settings_field(
      'emwa_field_menus',
      __('Other Menus', 'editor-menu-widget-access'),
      'emwa_field_menus_cb',
      'emwaPage',
      'emwaOther_section'
      /*[
          'label_for'         => 'emwa_field_menus',
          'class'             => 'emwa_row',
          'emwa_custom_data' => 'custom',
      ]*/
  );
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

function emwa_settings_section_callback(  ) { 
	echo __( 'Select the items to <strong>hide from Editors and Shop Managers</strong> throughout the admin area:', 'editor-menu-widget-access' );
}

function emwaOther_section_cb($args) {
	echo __( '<p>Select the other menus to <strong>hide from Editors and Shop Managers</strong>:</p>
    	<p><i>Please note: Some of these menu items are already hidden from Editors/Shop Managers, but you can select them again here to make sure.</i></p>' );
}
 
// Other Menus callback
 
function emwa_field_menus_cb($args) {

    $options = get_option('emwa_settings');
		$menuItems = $GLOBALS[ 'menu' ];
		$menuSubItems = $GLOBALS[ 'submenu' ];

		echo '<ol>';

		foreach ($menuItems as $menuItem) {
			if ( ($menuItem[0])!=="" 
				&& ($menuItem[1])!=="manage_options" 
				&& ($menuItem[1])!=="administrator" 
				&& ($menuItem[1])!=="list_users" 
				&& ($menuItem[1])!=="activate_plugins" 
				&& ($menuItem[2])!=="themes.php"
				) {

				echo '
				<li>'; ?>
					<input type="checkbox" name="emwa_settings[<?php echo $menuItem[2]; ?>]" value="1"<?php checked( isset( $options[$menuItem[2]] ) ); ?>/>
					<span style="font-weight: bold;"><?php echo $menuItem[0]; ?></span>
				</li>
				<ol>
				<?php
					// Submenus
					foreach($menuSubItems as $menuSubItem => $menuSubValues) {
					  if ( $menuSubItem == $menuItem[2] ) {
							foreach($menuSubValues as $menuSubValue => $menuSubValueValue) {
				  			if ( ($menuSubValueValue[2])!== $menuItem[2]
									&& ($menuSubValueValue[0])!=="" 
									&& ($menuSubValueValue[1])!=="manage_options" 
									&& ($menuSubValueValue[1])!=="administrator" 
									&& ($menuSubValueValue[1])!=="list_users" 
									&& ($menuSubValueValue[1])!=="activate_plugins" 
									&& ($menuSubValueValue[2])!=="themes.php"
									) {

								  echo '
								  <li>'; ?>
										<input type="checkbox" name="emwa_settings[<?php echo $menuSubValueValue[2]; ?>]" value="1" <?php checked( isset( $options[$menuSubValueValue[2]] ) ); ?>/>
										<span style=""><?php echo $menuSubValueValue[0]; ?></span>
									</li>
									<?php
								}

							}
					  }
					}
					echo '</ol>';
			}
		}
		echo '</ol>';
		//echo '<pre>' . print_r($menuSubItems, true) . '</pre>'; // For testing.
		?>

    <?php
}

/// Display the page

function emwa_options_page(  ) { 

	?>
	<div class="wrap">
		<form action='options.php' method='post'>

			<h2>Editor Menu and Widget Access</h2>

			<?php
			settings_fields( 'emwaPage' );
			do_settings_sections( 'emwaPage' );
			submit_button();
			?>

		</form>
	</div>
	<?php

}

?>