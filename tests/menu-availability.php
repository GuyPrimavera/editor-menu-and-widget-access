<?php
/** Role-only menu availability, inheritance and read-only guarantees. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable WordPress installation and EMWA_TEST_SITE=1.' );
}
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Admin\AccessPreview;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\Settings;

$original_config = get_option( Settings::OPTION, null );
$original_roles = get_option( wp_roles()->role_key );
$original_user = get_current_user_id();
$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( 'FAILED: ' . $message ); }
	++$checks;
	echo "PASS: $message\n";
};
$items = array();
foreach ( array(
	array( '', 'edit.php', 'edit_posts' ),
	array( '', 'options-general.php', 'manage_options' ),
	array( '', 'themes.php', 'switch_themes' ),
	array( 'themes.php', 'site-editor.php', 'edit_theme_options' ),
	array( 'themes.php', 'customize.php', 'customize' ),
	array( 'edit.php', 'edit-tags.php?taxonomy=post_tag', 'manage_post_tags' ),
	array( 'options-general.php', 'emwa-public-child', 'read' ),
	array( '', 'tools.php', 'read' ),
	array( 'tools.php', 'emwa-tool-child', 'read' ),
	array( '', 'emwa-unknown', '' ),
	array( '', 'emwa-object', 'edit_post' ),
	array( '', 'emwa-offline', 'read' ),
) as $row ) {
	$items[ $row[1] ] = array( 'parent' => $row[0], 'slug' => $row[1], 'capability' => $row[2], 'available' => 'emwa-offline' !== $row[1] );
}
try {
	get_role( 'editor' )->remove_cap( 'edit_theme_options' );
	$config = Settings::from_legacy( array( 'emwa_roles' => array( 'editor' => 1 ) ) );
	$config['profiles']['editor']['menus'][ Routes::id( '', 'edit.php' ) ] = array( 'parent' => '', 'slug' => 'edit.php', 'hidden' => true );
	$config['profiles']['editor']['menus'][ Routes::id( '', 'tools.php' ) ] = array( 'parent' => '', 'slug' => 'tools.php', 'hidden' => true );
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	wp_set_current_user( 1 );
	$role_snapshot = get_option( wp_roles()->role_key );
	$user_count = count_users()['total_users'];
	$results = AccessPreview::menu_rules( 'editor', $config['profiles']['editor'], $items );
	$check( $results['edit.php']['role_available'] && in_array( 'rule', $results['edit.php']['role_status'], true ), 'links hidden by Editor Access remain editable in the default list' );
	$check( ! $results['options-general.php']['role_available'] && in_array( 'permission', $results['options-general.php']['role_status'], true ), 'administrator-only Settings is excluded and labelled for Editor' );
	$check( $results['themes.php']['role_allowed'] && $results['site-editor.php']['role_available'], 'Appearance uses the edited role runtime grant rather than the administrator menu capability' );
	$check( $results['edit-tags.php?taxonomy=post_tag']['role_allowed'], 'WordPress meta capabilities such as Tags are mapped to role capabilities' );
	$check( ! $results['emwa-public-child']['role_available'] && in_array( 'parent_permission', $results['emwa-public-child']['role_status'], true ), 'an inaccessible parent explains why an otherwise permitted child is hidden' );
	$check( $results['emwa-tool-child']['role_available'] && in_array( 'parent_rule', $results['emwa-tool-child']['role_status'], true ), 'parent hide rules are labelled without discarding editable child rules' );
	$check( null === $results['emwa-unknown']['role_allowed'] && $results['emwa-unknown']['role_available'] && in_array( 'unknown', $results['emwa-unknown']['role_status'], true ), 'unknown permissions are labelled and remain discoverable' );
	$check( null === $results['emwa-object']['role_allowed'], 'object-specific capabilities are not guessed without an object' );
	$check( ! $results['emwa-offline']['role_available'] && in_array( 'unavailable', $results['emwa-offline']['role_status'], true ), 'unavailable plugin items are retained for Show all items' );
	$check( get_option( wp_roles()->role_key ) === $role_snapshot && get_option( Settings::OPTION ) === $config && count_users()['total_users'] === $user_count && 1 === get_current_user_id(), 'availability estimation changes no roles, settings, users or logged-in identity' );
	$profile = $config['profiles']['editor'];
	$profile['restrict_customizer'] = true;
	$restricted = AccessPreview::menu_rules( 'editor', $profile, $items );
	$check( ! $restricted['customize.php']['role_allowed'] && $restricted['site-editor.php']['role_allowed'], 'Customizer restriction is represented separately from Appearance grants' );
	get_role( 'editor' )->add_cap( 'edit_theme_options', false );
	$denied = AccessPreview::menu_rules( 'editor', $profile, $items );
	$check( ! $denied['site-editor.php']['role_allowed'], 'explicit stored denial defeats the temporary Appearance grant in the list' );
	get_role( 'editor' )->remove_cap( 'edit_theme_options' );
	$config['roles'] = array();
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	$inactive = AccessPreview::menu_rules( 'editor', $profile, $items );
	$check( ! $inactive['site-editor.php']['role_available'] && in_array( 'inactive_rule', $inactive['edit.php']['role_status'], true ), 'inactive profiles add no access and label saved rules as not applied' );
	add_role( 'emwa_menu_no_users', 'Menu role without accounts', array( 'read' => true ) );
	$empty = AccessPreview::menu_rules( 'emwa_menu_no_users', Settings::profile(), $items );
	$check( $empty['tools.php']['role_allowed'] && ! $empty['edit.php']['role_allowed'], 'roles without user accounts can be filtered from their own capabilities' );
	if ( is_multisite() ) {
		get_role( 'emwa_menu_no_users' )->add_cap( 'update_themes' );
		$network_items = array( 'theme-update' => array( 'parent' => '', 'slug' => 'emwa-theme-update', 'capability' => 'update_themes', 'available' => true ) );
		$network = AccessPreview::menu_rules( 'emwa_menu_no_users', Settings::profile(), $network_items );
		$check( ! $network['theme-update']['role_allowed'], 'multisite estimates do not inherit the viewing super administrator privileges' );
	}
	$missing = AccessPreview::menu_rules( 'emwa_nonexistent', Settings::profile(), $items );
	$check( null === $missing['edit.php']['role_allowed'] && in_array( 'unknown', $missing['edit.php']['role_status'], true ), 'deleted roles are clearly treated as unknown rather than inventing permissions' );
	$save_items = array();
	foreach ( $results as $item ) {
		$save_items[ Routes::id( $item['parent'], $item['slug'] ) ] = $item;
	}
	$patched = \EditorMenuWidgetAccess\Admin\SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'emwa_appearance_ui' => '2', 'hidden_appearance' => array(), 'hidden' => array( Routes::id( '', 'options-general.php' ) ) ), $save_items );
	$check( ! is_wp_error( $patched ) && $patched['profiles']['editor']['menus'][ Routes::id( '', 'options-general.php' ) ]['hidden'], 'a checked permission-filtered rule still survives a complete settings save' );
} finally {
	update_option( wp_roles()->role_key, $original_roles );
	wp_roles()->for_site();
	wp_set_current_user( $original_user );
	if ( null === $original_config ) { delete_option( Settings::OPTION ); } else { update_option( Settings::OPTION, $original_config, false ); }
	Settings::invalidate();
}
echo "Completed $checks role-menu availability assertions.\n";
