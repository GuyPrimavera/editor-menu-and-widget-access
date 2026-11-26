<?php
/** Disposable-site checks for reviewed profile copying and read-only access previews. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable WordPress installation and EMWA_TEST_SITE=1.' );
}
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Admin\AccessPreview;
use EditorMenuWidgetAccess\ProfileCopy;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\Settings;
$original_config  = get_option( Settings::OPTION, null );
$original_history = get_option( Settings::HISTORY, null );
$original_roles   = get_option( wp_roles()->role_key );
$original_user    = get_current_user_id();
$checks           = 0;
$check            = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'FAILED: ' . $message );
	}
	++$checks;
	echo "PASS: $message\n";
};
$user_id = wp_insert_user( array( 'user_login' => 'emwa_preview_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
try {
	$config = Settings::get();
	if ( ! Settings::writable() ) {
		update_option( Settings::OPTION, Settings::from_legacy( array( 'emwa_roles' => array( 'editor' => 1 ) ) ), false );
		Settings::invalidate();
		$config = Settings::get();
	}
	$config['roles']                         = array( 'editor' );
	$config['profiles']['editor']            = Settings::profile( 'editor' );
	$config['profiles']['editor']['menus']   = array( Routes::id( '', 'tools.php' ) => array( 'parent' => '', 'slug' => 'tools.php', 'hidden' => true ) );
	$config['profiles']['editor']['toolbar'] = array( 'wp-logo' => true );
	$config['profiles']['subscriber']        = Settings::profile( 'subscriber' );
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	$revision = Settings::revision( $config );
	$check( is_wp_error( ProfileCopy::copy( 'editor', 'subscriber', 'stale' ) ), 'stale copy is rejected' );
	$check( is_wp_error( ProfileCopy::copy( 'editor', 'administrator', $revision ) ), 'administrator target is rejected' );
	$check( is_wp_error( ProfileCopy::copy( 'editor', 'editor', $revision ) ), 'copying onto the same role is rejected' );
	$check( true === ProfileCopy::copy( 'editor', 'subscriber', $revision ), 'reviewed profile copy succeeds' );
	$next = Settings::get();
	$check( $next['profiles']['subscriber'] === $config['profiles']['editor'], 'all source profile settings copied exactly' );
	$check( $next['roles'] === $config['roles'], 'copy leaves selected roles unchanged' );
	$check( get_option( wp_roles()->role_key ) === $original_roles, 'copy leaves stored WordPress roles unchanged' );
	$history = get_option( Settings::HISTORY );
	$check( is_array( $history ) && end( $history )['settings'] === $config, 'copy backs up the previous configuration' );
	$GLOBALS['menu'] = array(
		2 => array( 'Posts', 'edit_posts', 'edit.php' ),
		8 => array( 'Tools', 'read', 'tools.php' ),
	);
	wp_set_current_user( $user_id );
	$restricted_preview = AccessPreview::for_role( 'editor' );
	$check( false === $restricted_preview['user'] && array() === $restricted_preview['menus'], 'users without list_users cannot inspect a sample account' );
	wp_set_current_user( 1 );
	$preview = AccessPreview::for_role( 'editor' );
	$check( $preview['user'] instanceof WP_User && true === $preview['appearance'], 'preview checks effective permission for a real Editor' );
	$tools = array_values( array_filter( $preview['menus'], static function ( $item ) { return 'Tools' === $item['label']; } ) );
	$check( 1 === count( $tools ) && $tools[0]['hidden'] && $tools[0]['allowed'], 'preview distinguishes a hidden link from its allowed capability' );
	$check( ! is_wp_error( Settings::validate( $next ) ), 'copied configuration remains valid' );
} finally {
	wp_set_current_user( $original_user );
	wp_delete_user( $user_id );
	if ( null === $original_config ) { delete_option( Settings::OPTION ); } else { update_option( Settings::OPTION, $original_config, false ); }
	if ( null === $original_history ) { delete_option( Settings::HISTORY ); } else { update_option( Settings::HISTORY, $original_history, false ); }
	Settings::invalidate();
}
echo "$checks profile-copy and preview assertions passed\n";
