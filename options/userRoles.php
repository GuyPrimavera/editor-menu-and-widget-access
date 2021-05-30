<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

function emwa_roles_section_cb() {
	echo '<p>' . __('Select the user roles to <strong>hide menus and other items</strong> from:', 'editor-menu-and-widget-access') . '</p>';
	echo '<p><em>' . __('Default: <strong>Editor</strong> and <strong>Shop Manager</strong>', 'editor-menu-and-widget-access') . '</em></p>';
}

// Roles callback
function emwa_roles_cb() {
	global $wp_roles;
	$userRoles = get_option('emwa_roles');
	$GLOBALS['emwaRoleString'] = '';

	if (empty($userRoles)) {
		add_option('emwa_roles_cap_added', array());
		$userRoles = array(
			'editor' => 1,
			'shop_manager' => 1
		);
	}

	if (!isset($wp_roles)) {
		$wp_roles = new WP_Roles();
	}

	$roles = $wp_roles->get_names();

	echo '<ul>';

	$capAdded = [];

	foreach ($roles as $role_key => $role_name) {
		$roleObject = get_role($role_key);
		if (!array_key_exists($role_key, $userRoles) && !$roleObject->has_cap('edit_theme_options')) {
			$capAdded[] = $role_key;
		}

		if ($role_key !== 'administrator' && $roleObject->has_cap('edit_posts')) {
?>
			<li>
				<input type='checkbox' name='emwa_roles[<?php echo $role_key; ?>]' <?php checked(isset($userRoles[$role_key])); ?> value='1'>
				<span><?php echo $role_name; ?></span>
			</li>
<?php
		}
	}

	// echo '<pre>'; print_r( get_option('emwa_roles_cap_added') ); echo '</pre>'; // Testing

	update_option('emwa_roles_cap_added', $capAdded);

	echo '</ul>';
}
