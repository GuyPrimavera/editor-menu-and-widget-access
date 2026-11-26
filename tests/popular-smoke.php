<?php
/** Disposable-site cross-plugin smoke check. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable WordPress installation and EMWA_TEST_SITE=1.' );
}
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Admin\SettingsPage;
$target = getenv( 'EMWA_SMOKE_PLUGIN' );
if ( ! $target ) {
	throw new RuntimeException( 'Set EMWA_SMOKE_PLUGIN.' );
}
$found = false;
foreach ( (array) get_option( 'active_plugins', array() ) as $active ) {
	if ( 0 === strpos( $active, $target . '/' ) ) {
		$found = true;
		break;
	}
}
if ( ! $found ) {
	throw new RuntimeException( $target . ' was not active.' );
}
$config = Settings::get();
if ( is_wp_error( Settings::validate( $config ) ) ) {
	throw new RuntimeException( $target . ' caused invalid Editor Access settings.' );
}
$admin = get_user_by( 'login', 'emwa-test-admin' );
$editor = get_user_by( 'login', 'emwa-browser-editor' );
if ( ! $admin || ! $editor || ! RolePolicy::exempt( $admin ) ) {
	throw new RuntimeException( $target . ' changed expected user roles.' );
}
$manifest = SettingsPage::manifest( 'menus', 'editor', array( 'route' => array( 'slug' => 'admin.php?page=one&amp;view=two' ) ) );
if ( is_wp_error( SettingsPage::decode_manifest( $manifest[0], $manifest[1], 'menus', 'editor' ) ) ) {
	throw new RuntimeException( $target . ' broke settings form integrity.' );
}
wp_set_current_user( $editor->ID );
if ( ! current_user_can( 'edit_theme_options' ) ) {
	throw new RuntimeException( $target . ' prevented the configured Editor appearance grant.' );
}
echo 'PASS: ' . $target . ' active with valid settings, role policy and signed forms' . "\n";
