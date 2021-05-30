<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

function emwa_new_adminbar() {
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
		global $wp_admin_bar;
		$adminbarOptions = get_option('emwaAdminbar');
		$barItems = $GLOBALS['adminbarArray'];

		if (is_array($barItems) || is_object($barItems)) {
			foreach ($barItems as $barItem) {
				$id = $barItem['id'];
				if ($id && isset($adminbarOptions[$id])) {
					$wp_admin_bar -> remove_menu($id);
				}
			}
		}
	}
}
add_action('wp_before_admin_bar_render', 'emwa_new_adminbar');
