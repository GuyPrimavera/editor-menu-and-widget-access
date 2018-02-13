<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

function emwa_new_adminbar() {

	$user = new WP_User(get_current_user_id());
	$role = false;

	if (!empty( $user->roles) && is_array($user->roles)) {
		foreach ($user->roles as $role)
			$role = $role;
	}

	if($role == "editor" || $role ==  "shop_manager") {

    global $wp_admin_bar;
	  $adminbarOptions = get_option('emwaAdminbar');
		$barItems = $GLOBALS['adminbarArray'];

		foreach ($barItems as $barItem) {

			if ( isset ( $adminbarOptions[$barItem['id']] ) ) { 
		    $wp_admin_bar->remove_menu( $barItem['id'] );
			}

		}
  }

}
add_action( 'wp_before_admin_bar_render', 'emwa_new_adminbar' );

?>