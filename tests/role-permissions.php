<?php
/** Explicit role-reset and deactivation semantics on a disposable WordPress site. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable WordPress installation and set EMWA_TEST_SITE=1.' );
}
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Capabilities;
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\Settings;

$checks = 0;
$check  = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'FAILED: ' . $message );
	}
	++$checks;
	echo "PASS: $message\n";
};
$original_settings = get_option( Settings::OPTION, null );
$original_roles    = get_option( wp_roles()->role_key );
$original_history  = get_option( RolePermissions::HISTORY, null );
$original_user     = get_current_user_id();
$original_legacy   = Migrations::legacy_options();
$original_backup   = get_option( Migrations::BACKUP, null );
$original_pending  = get_option( Migrations::LEGACY_DEACTIVATION, null );
$original_cleanup  = get_option( RolePermissions::CLEANUP_OPTION, null );
$custom_role       = 'emwa_reset_test_role';
try {
	$config = Settings::get();
	$config['roles'] = array_values( array_unique( array_merge( $config['roles'], array( 'editor' ) ) ) );
	$config['profiles']['editor'] = Settings::profile( 'editor' );
	$config['profiles']['editor']['restrict_customizer'] = true;
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	$editor = get_role( 'editor' );
	$editor->add_cap( 'edit_theme_options' );
	update_option( Migrations::LEGACY_DEACTIVATION, 1, false );
	$before_caps = $editor->capabilities;
	$revision = Settings::revision( Settings::get() );
	$check( is_wp_error( RolePermissions::reset( 'editor', $revision, 'stale-role-state' ) ) && ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ), 'stale role form cannot change permissions' );
	update_option( RolePermissions::HISTORY, 'invalid backup storage', false );
	$check( is_wp_error( RolePermissions::reset( 'editor', $revision, RolePermissions::revision( $editor ) ) ) && Settings::get() === $config && ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ), 'failed role backup leaves settings and capabilities unchanged' );
	delete_option( RolePermissions::HISTORY );
	$check( true === RolePermissions::reset( 'editor', $revision, RolePermissions::revision( $editor ) ), 'Editor reset succeeds' );
	$after = Settings::get();
	$check( ! $after['profiles']['editor']['grant_appearance'] && ! $after['profiles']['editor']['restrict_customizer'], 'Editor reset disables plugin grants and restrictions' );
	$check( ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'Editor stored appearance capability returns to core default' );
	$check( false === get_option( Migrations::LEGACY_DEACTIVATION, false ), 'explicit Editor reset clears pending legacy deactivation cleanup' );
	$check( get_option( RolePermissions::HISTORY )[0]['capabilities'] === $before_caps, 'full prior role capabilities are backed up before removal' );
	$check( array_diff_assoc( $before_caps, get_role( 'editor' )->capabilities ) === array( 'edit_theme_options' => true ), 'unrelated Editor capabilities remain unchanged' );
	$check( is_wp_error( RolePermissions::reset( 'administrator', Settings::revision( $after ), '' ) ), 'administrator role cannot be reset' );

	add_role( $custom_role, 'EMWA reset test', array( 'read' => true, 'edit_theme_options' => true ) );
	$config = Settings::get();
	$config['profiles'][ $custom_role ] = Settings::profile( $custom_role );
	$config['profiles'][ $custom_role ]['grant_appearance'] = true;
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	$custom = get_role( $custom_role );
	$check( true === RolePermissions::reset( $custom_role, Settings::revision( Settings::get() ), RolePermissions::revision( $custom ) ), 'plugin-defined role reset succeeds' );
	$check( ! Settings::get()['profiles'][ $custom_role ]['grant_appearance'] && ! empty( get_role( $custom_role )->capabilities['edit_theme_options'] ), 'custom-role stored capability is preserved because no WordPress default exists' );

	$config = Settings::get();
	$config['profiles']['editor']['grant_appearance'] = true;
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	$editor_id = wp_insert_user( array( 'user_login' => 'emwa_reset_editor_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
	wp_set_current_user( $editor_id );
	$check( current_user_can( 'edit_theme_options' ), 'plugin adds Appearance permission only while its capability filter runs' );
	remove_filter( 'user_has_cap', array( Capabilities::class, 'grant' ), 20 );
	$check( ! current_user_can( 'edit_theme_options' ), 'deactivation removes runtime grant without changing stored role' );
	add_filter( 'user_has_cap', array( Capabilities::class, 'grant' ), 20, 4 );
	$check( ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'deactivation simulation does not write role permissions' );
	wp_delete_user( $editor_id );
	wp_set_current_user( $original_user );

	$check( false !== has_action( 'activate_' . plugin_basename( EMWA_PLUGIN_FULL_PATH ), array( RolePermissions::class, 'activate' ) ), 'real plugin activation hook is registered' );
	$check( false === has_action( 'deactivate_' . plugin_basename( EMWA_PLUGIN_FULL_PATH ) ), 'deactivation has no stored-role mutation hook' );
	foreach ( Migrations::LEGACY_OPTIONS as $option ) {
		delete_option( $option );
	}
	delete_option( Settings::OPTION );
	delete_option( Migrations::BACKUP );
	delete_option( RolePermissions::CLEANUP_OPTION );
	update_option( 'emwa_roles', array( 'editor' => 1, 'administrator' => 1, $custom_role => 1 ), false );
	update_option( Migrations::LEGACY_DEACTIVATION, 1, false );
	foreach ( RolePermissions::LEGACY_CAPS as $cap ) {
		get_role( 'editor' )->add_cap( $cap );
	}
	get_role( 'author' )->add_cap( 'edit_theme_options' );
	get_role( 'subscriber' )->add_cap( 'edit_theme_options', false );
	get_role( 'administrator' )->remove_cap( 'edit_themes' );
	get_role( 'administrator' )->remove_cap( 'update_themes' );
	get_role( 'administrator' )->add_cap( 'delete_themes', false );
	$shop = get_role( 'shop_manager' );
	$shop_caps = $shop ? $shop->capabilities : null;
	$custom_caps = get_role( $custom_role )->capabilities;
	$before = get_option( wp_roles()->role_key );
	update_option( RolePermissions::HISTORY, 'invalid backup storage', false );
	$check( is_wp_error( RolePermissions::cleanup() ) && get_option( wp_roles()->role_key ) === $before, 'failed activation backup leaves every role unchanged' );
	$check( false === get_option( RolePermissions::CLEANUP_OPTION, false ) && 1 === get_option( Migrations::LEGACY_DEACTIVATION ), 'failed cleanup remains pending for retry' );
	delete_option( RolePermissions::HISTORY );
	// Exercise the registered activation callback with already-existing v4 settings too.
	Migrations::run();
	$config = Settings::get();
	$config['roles'] = array( 'editor' );
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	do_action( 'activate_' . plugin_basename( EMWA_PLUGIN_FULL_PATH ), false );
	$check( array() === array_intersect_key( get_role( 'editor' )->capabilities, array_flip( RolePermissions::LEGACY_CAPS ) ), 'activation removes all six non-default theme grants from Editor' );
	$check( ! array_key_exists( 'edit_theme_options', get_role( 'author' )->capabilities ), 'other built-in content roles return to their theme defaults' );
	$check( false === get_role( 'subscriber' )->capabilities['edit_theme_options'], 'explicit capability denials are preserved' );
	$check( ! empty( get_role( 'administrator' )->capabilities['edit_themes'] ) && ! empty( get_role( 'administrator' )->capabilities['update_themes'] ), 'missing Administrator grants removed by legacy settings are restored' );
	$check( false === get_role( 'administrator' )->capabilities['delete_themes'], 'Administrator explicit denial is not overwritten' );
	$check( get_role( $custom_role )->capabilities === $custom_caps, 'custom-role permissions remain unchanged because their defaults are unknown' );
	$check( ! $shop || get_role( 'shop_manager' )->capabilities === $shop_caps, 'activation preserves WooCommerce-owned Shop Manager defaults' );
	$check( Settings::get() === $config, 'activation cleanup preserves existing v4 profiles and selections' );
	$check( RolePermissions::CLEANUP_VERSION === get_option( RolePermissions::CLEANUP_OPTION ) && false === get_option( Migrations::LEGACY_DEACTIVATION, false ), 'successful cleanup records its version and retires the deactivation marker' );
	$history = get_option( RolePermissions::HISTORY );
	$backed_up = array_column( $history, 'capabilities', 'role' );
	$check( $backed_up['editor'] === $before['editor']['capabilities'] && $backed_up['administrator'] === $before['administrator']['capabilities'], 'activation backs up complete changed roles before modifying them' );
	$editor_expected = array_diff_key( $before['editor']['capabilities'], array_flip( RolePermissions::LEGACY_CAPS ) );
	$check( get_role( 'editor' )->capabilities === $editor_expected, 'activation retains all unrelated Editor capabilities' );
	RolePermissions::activate();
	$check( get_option( RolePermissions::HISTORY ) === $history, 'repeated activation without drift creates no redundant role backup' );
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	RolePermissions::maybe_cleanup();
	$check( ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ), 'ordinary requests do not repeatedly rewrite role permissions' );
	RolePermissions::activate();
	$check( ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'each explicit reactivation cleans a newly stored legacy grant' );
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	delete_option( RolePermissions::CLEANUP_OPTION );
	RolePermissions::maybe_cleanup();
	$check( ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'upgrade without activation runs versioned cleanup once' );
	// Missing core roles must never make activation fatal.
	$subscriber = get_role( 'subscriber' )->capabilities;
	remove_role( 'subscriber' );
	RolePermissions::activate();
	$check( null === get_role( 'subscriber' ), 'missing built-in role is skipped without recreating it' );
	add_role( 'subscriber', 'Subscriber', $subscriber );
	foreach ( Migrations::LEGACY_OPTIONS as $option ) {
		delete_option( $option );
	}
	delete_option( Settings::OPTION );
	delete_option( Migrations::BACKUP );
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	RolePermissions::activate();
	$check( array( 'editor', 'shop_manager' ) === Settings::get()['roles'] && ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ), 'fresh activation also cleans stored Editor grants and retains default profiles' );
	$check( ! empty( get_option( Migrations::BACKUP )['capabilities']['editor']['edit_theme_options'] ), 'original migration backup predates activation cleanup' );
} finally {
	wp_set_current_user( $original_user );
	update_option( wp_roles()->role_key, $original_roles );
	wp_roles()->for_site();
	if ( null === $original_settings ) {
		delete_option( Settings::OPTION );
	} else {
		update_option( Settings::OPTION, $original_settings, false );
	}
	Settings::invalidate();
	foreach ( Migrations::LEGACY_OPTIONS as $option ) {
		delete_option( $option );
	}
	foreach ( $original_legacy as $option => $value ) {
		update_option( $option, $value, false );
	}
	foreach ( array( Migrations::BACKUP => $original_backup, Migrations::LEGACY_DEACTIVATION => $original_pending, RolePermissions::CLEANUP_OPTION => $original_cleanup ) as $option => $value ) {
		if ( null === $value ) {
			delete_option( $option );
		} else {
			update_option( $option, $value, false );
		}
	}
	if ( null === $original_history ) {
		delete_option( RolePermissions::HISTORY );
	} else {
		update_option( RolePermissions::HISTORY, $original_history, false );
	}
}
$check( get_option( wp_roles()->role_key ) === $original_roles, 'disposable role capabilities restored after tests' );
echo "Completed $checks role-permission assertions.\n";
