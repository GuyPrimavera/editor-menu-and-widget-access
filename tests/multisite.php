<?php
/** Isolated multisite migration and blog-switch checks. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) || ! is_multisite() ) {
	throw new RuntimeException( 'Use a disposable multisite and EMWA_TEST_SITE=1.' );
}
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\ProfileCopy;

$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'FAILED: ' . $message );
	}
	++$checks;
	echo "PASS: $message\n";
};
$main_id = get_current_blog_id();
$main = Settings::get();
$main_editor_caps = get_role( 'editor' )->capabilities;
$check( ! is_wp_error( Settings::validate( $main ) ), 'main-site settings are valid' );
$site = wp_insert_site( array(
	'domain' => get_site( $main_id )->domain,
	'path' => '/emwa-child-' . strtolower( wp_generate_password( 8, false ) ) . '/',
	'title' => 'EMWA child test',
	'network_id' => get_current_network_id(),
) );
if ( is_wp_error( $site ) ) {
	throw new RuntimeException( $site->get_error_message() );
}
$child_id = (int) $site;
switch_to_blog( $child_id );
$check( get_current_blog_id() === $child_id, 'new child site is active' );
$legacy = array( 'emwa_chk_widgets' => 1, 'admin.php?page=child-only' => 1 );
update_option( 'emwa_settings', $legacy );
update_option( 'emwa_roles', array( 'editor' => 1 ) );
get_role( 'editor' )->add_cap( 'edit_theme_options' );
Migrations::run();
$child = Settings::get();
$check( $child['roles'] === array( 'editor' ), 'child site migrates its own selected roles' );
$check( ! empty( $child['profiles']['editor']['hidden_appearance']['widgets'] ), 'child site migrates its own appearance rules' );
$check( get_option( Migrations::BACKUP )['options']['emwa_settings'] === $legacy, 'child site has its own immutable legacy backup' );
$check( false === get_option( RolePermissions::CLEANUP_OPTION, false ), 'legacy child site still requires activation cleanup' );
restore_current_blog();
RolePermissions::activate( true );
switch_to_blog( $child_id );
$check( ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ) && RolePermissions::CLEANUP_VERSION === get_option( RolePermissions::CLEANUP_OPTION ), 'network activation cleans legacy Editor grant on child site' );
$child['profiles']['editor']['toolbar']['child-only'] = true;
update_option( Settings::OPTION, $child, false );
Settings::invalidate();
$child_editor = get_role( 'editor' );
$child_editor->add_cap( 'edit_theme_options' );
$reset = RolePermissions::reset( 'editor', Settings::revision( Settings::get() ), RolePermissions::revision( $child_editor ) );
$check( true === $reset && ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'child-site reset removes only its stored Editor grant' );
$check( ! Settings::get()['profiles']['editor']['grant_appearance'] && get_option( RolePermissions::HISTORY )[0]['role'] === 'editor', 'child-site reset stores settings and capability backup on that site' );
$child_before_copy = Settings::get();
$check( true === ProfileCopy::copy( 'editor', 'subscriber', Settings::revision( $child_before_copy ) ), 'child-site profile copy succeeds' );
$check( Settings::get()['profiles']['subscriber'] === $child_before_copy['profiles']['editor'] && Settings::get()['roles'] === $child_before_copy['roles'], 'child-site profile copy preserves its selected roles' );
restore_current_blog();
$check( get_role( 'editor' )->capabilities === $main_editor_caps, 'child-site role reset leaves main-site Editor capabilities unchanged' );
$check( get_current_blog_id() === $main_id && Settings::get() === $main, 'switching back restores main-site settings without cache bleed' );
$check( ! isset( Settings::get()['profiles']['editor']['toolbar']['child-only'] ), 'child toolbar rule does not leak to main site' );
$admin = get_user_by( 'login', 'admin' );
$check( $admin instanceof WP_User && RolePolicy::exempt( $admin ), 'network super administrator remains exempt' );
switch_to_blog( $child_id );
$check( Settings::get()['profiles']['editor']['toolbar']['child-only'], 'switching again retrieves child configuration' );
restore_current_blog();
$check( get_option( Settings::OPTION ) === $main, 'main configuration remains byte-equivalent after child edits' );
echo "Completed $checks multisite assertions.\n";
