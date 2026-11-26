<?php
/** Failed network activation must preflight every site's backup and restore blog context. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) || ! is_multisite() ) { throw new RuntimeException( 'Disposable multisite only.' ); }
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\Settings;
global $wpdb;
$main_id = get_current_blog_id();
$main_roles = get_option( wp_roles()->role_key );
$main_history = get_option( RolePermissions::HISTORY, null );
$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) { if ( ! $condition ) { throw new RuntimeException( 'FAILED: ' . $message ); } ++$checks; echo "PASS: $message\n"; };
$child = wp_insert_site( array( 'domain' => get_site( $main_id )->domain, 'path' => '/audit-child-' . strtolower( wp_generate_password( 8, false ) ) . '/', 'network_id' => get_current_network_id() ) );
if ( is_wp_error( $child ) ) { throw new RuntimeException( $child->get_error_message() ); }
$child = (int) $child;
$wpdb->insert( $wpdb->site, array( 'domain' => 'emwa-other-network.test', 'path' => '/' ) );
$other_network = (int) $wpdb->insert_id;
clean_network_cache( $other_network );
$other_site = wp_insert_site( array( 'domain' => 'emwa-other-network.test', 'path' => '/', 'network_id' => get_current_network_id() ) );
if ( is_wp_error( $other_site ) ) { throw new RuntimeException( $other_site->get_error_message() ); }
$other_site = (int) $other_site;
wp_update_site( $other_site, array( 'network_id' => $other_network ) );
switch_to_blog( $other_site );
get_role( 'editor' )->add_cap( 'edit_theme_options' );
restore_current_blog();
$die_handler = static function () { return static function () { throw new RuntimeException( 'Expected network activation failure.' ); }; };
try {
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	switch_to_blog( $child );
	Migrations::run();
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	update_option( RolePermissions::HISTORY, 'corrupt', false );
	restore_current_blog();
	add_filter( 'wp_die_handler', $die_handler, PHP_INT_MAX );
	$failed = false;
	try { RolePermissions::activate( true ); } catch ( RuntimeException $error ) { $failed = true; }
	remove_filter( 'wp_die_handler', $die_handler, PHP_INT_MAX );
	$check( $failed && $main_id === get_current_blog_id(), 'network activation failure restores the original blog context before reporting the error' );
	$check( ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ), 'a later site backup failure leaves the earlier site capabilities unchanged' );
	switch_to_blog( $child );
	$check( ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ), 'the failing child site also retains its stored permission' );
	delete_option( RolePermissions::HISTORY );
	restore_current_blog();
	RolePermissions::activate( true );
	switch_to_blog( $other_site );
	$check( ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ) && null === get_option( RolePermissions::HISTORY, null ), 'network activation leaves sites on a different network untouched' );
	restore_current_blog();
	$check( $main_id === get_current_blog_id() && ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'successful retry cleans main-site defaults and restores blog context' );
	$check( ! empty( get_option( RolePermissions::HISTORY )[0]['capabilities']['edit_theme_options'] ), 'main-site capability backup remains available after cleanup' );
	switch_to_blog( $child );
	$check( ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ) && ! empty( get_option( RolePermissions::HISTORY )[0]['capabilities']['edit_theme_options'] ), 'child-site cleanup is backed up independently' );
	restore_current_blog();
} finally {
	remove_filter( 'wp_die_handler', $die_handler, PHP_INT_MAX );
	while ( get_current_blog_id() !== $main_id && ms_is_switched() ) { restore_current_blog(); }
	update_option( wp_roles()->role_key, $main_roles );
	wp_roles()->for_site();
	if ( null === $main_history ) { delete_option( RolePermissions::HISTORY ); } else { update_option( RolePermissions::HISTORY, $main_history, false ); }
	wp_delete_site( $child );
	wp_delete_site( $other_site );
	$wpdb->delete( $wpdb->sitemeta, array( 'site_id' => $other_network ) );
	$wpdb->delete( $wpdb->site, array( 'id' => $other_network ) );
	clean_network_cache( $other_network );
	Settings::invalidate();
}
echo "Completed $checks network audit assertions.\n";
