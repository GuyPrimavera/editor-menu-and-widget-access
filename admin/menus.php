<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

function emwa_new_admin_menu() {
	$userRoles = get_option('emwa_roles');
	$user = new WP_User(get_current_user_id());
	$role = false; // @howdy_mcgee

	if (!empty($user -> roles) && is_array($user -> roles)) {
		foreach ($user -> roles as $role) {
			$role = $role;
		}
	}

	if (
		(!empty($userRoles) && array_key_exists($role, $userRoles)) ||
		(empty($userRoles) && ($role == "editor" || $role ==  "shop_manager"))
	) {
		$emwaOptions = get_option('emwa_settings');
		global $submenu;

		unset($submenu['themes.php'][5]);

		// Customize Submenu
		if (isset($emwaOptions['emwa_chk_custom'])) {
			unset($submenu['themes.php'][6]);
			unset($submenu['themes.php'][15]);
			unset($submenu['themes.php'][20]);
		}

		// Widgets Submenu
		if (isset($emwaOptions['emwa_chk_widgets'])) {
			remove_submenu_page('themes.php', 'widgets.php');
		}

		// Menus Submenu
		if (isset($emwaOptions['emwa_chk_menus'])) {
			unset($submenu['themes.php'][10]);
		}

		// Hide Appearance Menu if all submenus are set to "hidden"
		if (
			(isset($emwaOptions['emwa_chk_custom'])) &&
			(isset($emwaOptions['emwa_chk_widgets'])) &&
			(isset($emwaOptions['emwa_chk_menus']))
		) {
			remove_menu_page('themes.php');
		}

		// Other Menus
		$menuItems = $GLOBALS['menu'];

		foreach ($menuItems as $menuItem) {
			if (
				($menuItem[0]) !== "" &&
				($menuItem[1]) !== "manage_options" &&
				($menuItem[1]) !== "administrator" &&
				($menuItem[1]) !== "list_users" &&
				($menuItem[1]) !== "activate_plugins" &&
				($menuItem[2]) !== "themes.php"
			) {

				if (isset($emwaOptions[$menuItem[2]])) {
					remove_menu_page($menuItem[2]);
				}
			}
		}

		// Sneaky Visual Composer
		if (isset($emwaOptions['vc-general'])) {
			remove_menu_page('vc-welcome');
		}

		// Submenus
		$menuSubItems = $GLOBALS['submenu'];

		foreach ($menuSubItems as $menuSubItem => $menuSubValues) {
			foreach ($menuSubValues as $menuSubValue => $menuSubValueValue) {
				if (isset($emwaOptions[$menuSubValueValue[2]])) {
					remove_submenu_page($menuSubItem, $menuSubValueValue[2]);
				}
			}
		}
	}
}
add_action('admin_menu', 'emwa_new_admin_menu', 99);

/// Customizer modifications
function emwa_customiser_mods($components) {
	$userRoles = get_option('emwa_roles');
	$user = new WP_User(get_current_user_id());
	$role = false;

	if (!empty($user -> roles) && is_array($user -> roles)) {
		foreach ($user -> roles as $role) {
			$role = $role;
		}
	}

	if (
		(!empty($userRoles) && array_key_exists($role, $userRoles)) ||
		(empty($userRoles) && ($role == "editor" || $role ==  "shop_manager"))
	) {

		$emwaOptions = get_option('emwa_settings');

		$widgets = array_search('widgets', $components);
		$menus = array_search('nav_menus', $components);
		if (!!$widgets && !!$menus) {
			if (isset($emwaOptions['emwa_chk_widgets'])) {
				unset($components[$widgets]);
			}
			if (isset($emwaOptions['emwa_chk_menus'])) {
				unset($components[$menus]);
			}
		}
	}
	return $components;
}
add_filter('customize_loaded_components', 'emwa_customiser_mods');

/// Adminbar links
function emwa_adminbar_link() {
	$userRoles = get_option('emwa_roles');
	$user = new WP_User(get_current_user_id());
	$role = false;

	if (!empty($user -> roles) && is_array($user -> roles)) {
		foreach ($user -> roles as $role) {
			$role = $role;
		}
	}

	if (
		(!empty($userRoles) && array_key_exists($role, $userRoles)) ||
		(empty($userRoles) && ($role == "editor" || $role ==  "shop_manager"))
	) {

		$emwaOptions = get_option('emwa_settings');
		global $wp_admin_bar;

		$wp_admin_bar -> remove_menu('themes');

		if (isset($emwaOptions['emwa_chk_custom'])) {
			$wp_admin_bar -> remove_menu('customize');
		}

		if (isset($emwaOptions['emwa_chk_widgets'])) {
			$wp_admin_bar -> remove_menu('widgets');
		}

		if (isset($emwaOptions['emwa_chk_menus'])) {
			$wp_admin_bar -> remove_menu('menus');
		}
	}
}
add_action('wp_before_admin_bar_render', 'emwa_adminbar_link', 999);

/// Customizer options - failsafe
function emwa_hide_custom() {
	$userRoles = get_option('emwa_roles');
	$user = new WP_User(get_current_user_id());
	$role = false;

	if (!empty($user -> roles) && is_array($user -> roles)) {
		foreach ($user -> roles as $role) {
			$role = $role;
		}
	}

	$emwaOptions = get_option('emwa_settings');

	if (
		(!empty($userRoles) && array_key_exists($role, $userRoles)) ||
		(empty($userRoles) && ($role == "editor" || $role ==  "shop_manager"))
	) {
		if (isset($emwaOptions['emwa_chk_custom'])) {
			echo "<style type='text/css' media='screen'>
				.hide-if-no-customize { display: none!important; }
			</style>
			";
		}
	}
}
add_action('admin_head', 'emwa_hide_custom', 99);
