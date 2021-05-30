<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

// Callback
function emwa_settings_section_callback() {
	$roleString = get_option('emwa_role_string');

	echo __('Select the items to <strong>hide</strong> from the <strong>' . $roleString . '</strong> role(s) throughout the admin area:', 'editor-menu-and-widget-access');
}

// Render checkboxes
function emwa_chk_themes_render() {
	$options = get_option('emwa_settings');
	if (isset($options['emwa_chk_themes'])) {
		$emwaThemes = $options['emwa_chk_themes'];
	} else {
		$emwaThemes = 0;
	};

?>
	<input type='checkbox' name='emwa_settings[emwa_chk_themes]' <?php checked($emwaThemes, 1); ?> value='1' checked disabled>
<?php
	echo '<em>' . __('Always hidden.', 'editor-menu-and-widget-access') . '</em>';
}


function emwa_chk_custom_render() {
	$options = get_option('emwa_settings');
	if (isset($options['emwa_chk_custom'])) {
		$emwaCustom = $options['emwa_chk_custom'];
	} else {
		$emwaCustom = 0;
	};

?>
	<input type='checkbox' name='emwa_settings[emwa_chk_custom]' <?php checked($emwaCustom, 1); ?> value='1'>
<?php
	echo '<em>' . __('Including "Header" and "Background".', 'editor-menu-and-widget-access') . '</em>';
}


function emwa_chk_widgets_render() {
	$options = get_option('emwa_settings');
	if (isset($options['emwa_chk_widgets'])) {
		$emwaWidgets = $options['emwa_chk_widgets'];
	} else {
		$emwaWidgets = 0;
	};

?>
	<input type='checkbox' name='emwa_settings[emwa_chk_widgets]' <?php checked($emwaWidgets, 1); ?> value='1'>
<?php
}


function emwa_chk_menus_render() {
	$options = get_option('emwa_settings');
	if (isset($options['emwa_chk_menus'])) {
		$emwaMenus = $options['emwa_chk_menus'];
	} else {
		$emwaMenus = 0;
	};

?>
	<input type='checkbox' name='emwa_settings[emwa_chk_menus]' <?php checked($emwaMenus, 1); ?> value='1'>
<?php
}
