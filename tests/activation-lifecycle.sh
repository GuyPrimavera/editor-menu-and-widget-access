#!/usr/bin/env bash
# Real activation/deactivation in separate requests. Disposable installations only.
set -euo pipefail
if [[ ${EMWA_TEST_SITE:-} != 1 || -z ${1:-} ]]; then
    echo 'Use EMWA_TEST_SITE=1 and pass a disposable WordPress path.' >&2
    exit 1
fi
wp_test=(wp --path="$1")
slug=editor-menu-and-widget-access
"${wp_test[@]}" plugin deactivate "$slug" >/dev/null
"${wp_test[@]}" eval '
require_once WP_PLUGIN_DIR . "/editor-menu-and-widget-access/editor-menu-widget-access.php";
$config = \EditorMenuWidgetAccess\Settings::from_legacy( array() );
$config["roles"] = array( "editor" );
update_option( "emwa_config", $config, false );
get_role( "editor" )->add_cap( "edit_theme_options" );
update_option( "emwa_role_permission_backups", "invalid backup storage", false );
' --skip-plugins --skip-themes
if "${wp_test[@]}" plugin activate "$slug" >/tmp/emwa-activation-failure.log 2>&1; then
    echo 'FAIL: activation must fail when the backup cannot be written' >&2
    exit 1
fi
"${wp_test[@]}" eval '
require_once ABSPATH . "wp-admin/includes/plugin.php";
if ( is_plugin_active( "editor-menu-and-widget-access/editor-menu-widget-access.php" ) || empty( get_role( "editor" )->capabilities["edit_theme_options"] ) ) {
    throw new RuntimeException( "Failed activation changed permissions or left the plugin active" );
}
delete_option( "emwa_role_permission_backups" );
' --skip-plugins --skip-themes
printf 'PASS: failed activation retains the stored grant and leaves the plugin inactive\n'
"${wp_test[@]}" plugin activate "$slug"
"${wp_test[@]}" eval '
$login = "emwa_lifecycle_editor";
$user = get_user_by( "login", $login );
$id = $user ? $user->ID : wp_insert_user( array( "user_login" => $login, "user_pass" => wp_generate_password(), "role" => "editor" ) );
if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
wp_set_current_user( $id );
if ( array_key_exists( "edit_theme_options", get_role( "editor" )->capabilities ) || ! current_user_can( "edit_theme_options" ) ) {
    throw new RuntimeException( "Active plugin must provide a runtime grant without a stored grant" );
}
$history = get_option( "emwa_role_permission_backups" );
if ( empty( $history[0]["capabilities"]["edit_theme_options"] ) ) {
    throw new RuntimeException( "Activation did not back up the original role" );
}
'
printf 'PASS: activation backs up and removes the stored grant; runtime access works in a new request\n'
"${wp_test[@]}" plugin deactivate "$slug"
"${wp_test[@]}" eval '
$user = get_user_by( "login", "emwa_lifecycle_editor" );
wp_set_current_user( $user->ID );
if ( current_user_can( "edit_theme_options" ) || current_user_can( "customize" ) || array_key_exists( "edit_theme_options", get_role( "editor" )->capabilities ) ) {
    throw new RuntimeException( "Inactive plugin left appearance access behind" );
}
$request = new WP_REST_Request( "POST", "/wp/v2/templates" );
$request->set_param( "slug", "emwa-lifecycle-template" );
$response = rest_do_request( $request );
if ( 403 !== $response->get_status() ) {
    throw new RuntimeException( "Inactive Editor could access template creation" );
}
wp_set_current_user( 0 );
require_once ABSPATH . "wp-admin/includes/user.php";
wp_delete_user( $user->ID );
'
printf 'PASS: deactivation ends appearance access and core REST denies template creation\n'
