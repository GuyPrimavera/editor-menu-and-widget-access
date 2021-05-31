<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

function emwa_set_role_string() {
  global $wp_roles;
  $userRoles = get_option('emwa_roles');
  $roleString = get_option('emwa_role_string');
  $shopMan = get_role('shop_manager');

  if (empty($userRoles)) {
    if (!empty($shopMan)) {
      $userRoles = array (
        'editor' => 1,
        'shop_manager' => 1
      );
    } else {
      $userRoles = array (
        'editor' => 1
      );
    }
  }

  if (!empty($userRoles)) {
    $roleArray = [];
    $roleStringTemp = '';
    $i = 0;

    if (empty($roleString)) {
      add_option('emwa_role_string', '');
    }

    foreach ($userRoles as $role_key => $role_name) {
      $roleArray[] = translate_user_role($wp_roles->roles[$role_key]['name']);
    }

    foreach ($roleArray as $role_name) {
      if ($i > 0) {
        if ($i === count($roleArray) - 1) {
          $roleStringTemp .= ' and ';
        } else {
          $roleStringTemp .= ', ';
        }
      }
      $roleStringTemp .= $role_name;
      $i++;
    }

    update_option('emwa_role_string', $roleStringTemp);
  }
}
add_action('init', 'emwa_set_role_string');
