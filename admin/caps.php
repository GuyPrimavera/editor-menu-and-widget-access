<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

// Remove access to Themes page.
function emwa_set_capabilities() {
  $userRoles = get_option('emwa_roles');

  $caps = array(
    'edit_themes',
    'update_themes',
    'delete_themes',
    'install_themes',
    'upload_themes'
  );

  if (!empty($userRoles)) {
    foreach ($userRoles as $role_key => $role_name) {
      $role = get_role($role_key);

      if ($role_key === 'editor' || $role_key === 'shop_manager') {
        $role->add_cap('edit_theme_options');
      }
      
      foreach ($caps as $cap) {
        $role -> remove_cap($cap);
      }
    }
  } else {
    $editor = get_role('editor');
    $shopMan = get_role('shop_manager');
    $editor -> add_cap('edit_theme_options');
    
    if (!empty($shopMan)) {
      $shopMan -> add_cap('edit_theme_options');
    }

    foreach ($caps as $cap) {
      if (!empty($editor)) {
        $editor -> remove_cap($cap);
      }

      if (!empty($shopMan)) {
        $shopMan -> remove_cap($cap);
      }
    }
  }
}
add_action('init', 'emwa_set_capabilities');
