<?php
/** Reproductions of edge cases found by the full lifecycle and compatibility audit. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) { throw new RuntimeException( 'Disposable test site only.' ); }
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\MenuVisibility;
use EditorMenuWidgetAccess\Admin\Catalog;
use EditorMenuWidgetAccess\Admin\AccessPreview;
use EditorMenuWidgetAccess\Admin\SettingsPage;

$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( 'FAILED: ' . $message ); }
	++$checks;
	echo "PASS: $message\n";
};
$options = array_merge( Migrations::LEGACY_OPTIONS, array( Settings::OPTION, Settings::HISTORY, Migrations::BACKUP, RolePermissions::HISTORY, RolePermissions::CLEANUP_OPTION, Migrations::LEGACY_DEACTIVATION ) );
$original = array();
foreach ( $options as $name ) { $original[ $name ] = get_option( $name, null ); }
$original_roles = get_option( wp_roles()->role_key );
$original_user = get_current_user_id();
$original_get = $_GET;
$original_menu = $GLOBALS['menu'] ?? array();
$original_submenu = $GLOBALS['submenu'] ?? array();
$theme = get_stylesheet();
$editor_user = get_user_by( 'login', 'emwa-browser-editor' );
$created_user = false;
if ( ! $editor_user ) {
	$editor_id = wp_insert_user( array( 'user_login' => 'emwa_audit_editor_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
	if ( is_wp_error( $editor_id ) ) { throw new RuntimeException( $editor_id->get_error_message() ); }
	$editor_user = get_userdata( $editor_id );
	$created_user = true;
}
$clear = static function () use ( $options ) {
	foreach ( $options as $option ) { delete_option( $option ); }
	Settings::invalidate();
};
$write = static function ( $config ) { update_option( Settings::OPTION, $config, false ); Settings::invalidate(); };
set_error_handler( static function ( $level, $message, $file, $line ) { throw new ErrorException( $message, 0, $level, $file, $line ); }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );
try {
	$clear();
	update_option( Migrations::BACKUP, 'corrupt', false );
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	$before = get_option( wp_roles()->role_key );
	$check( is_wp_error( Migrations::run() ) && is_wp_error( RolePermissions::cleanup() ) && get_option( wp_roles()->role_key ) === $before, 'corrupt migration backup blocks capability cleanup without changing any role' );
	$check( array() === Settings::get()['roles'], 'missing settings do not silently grant default access after failed migration' );
	$clear();
	$legacy = array( 'emwa_roles' => array( 'editor' => 1 ), 'emwa_settings' => array( 'emwa_chk_widgets' => 1 ) );
	update_option( Migrations::BACKUP, array( 'options' => $legacy, 'capabilities' => array() ), false );
	update_option( 'emwa_settings', array( 'emwa_chk_menus' => 1 ), false );
	$check( true === Migrations::run() && ! empty( Settings::get()['profiles']['editor']['hidden_appearance']['widgets'] ) && empty( Settings::get()['profiles']['editor']['hidden_appearance']['menus'] ), 'migration uses the original persisted snapshot rather than changed live legacy options' );
	$clear();
	$config = Settings::from_legacy( array() );
	$write( $config );
	$check( true === Migrations::run() && Settings::get() === $config && is_array( get_option( Migrations::BACKUP ) ), 'existing valid v4 settings without an original backup are preserved and safely snapshotted' );

	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	$role_key = wp_roles()->role_key;
	$inject = static function ( $option ) use ( $role_key ) {
		if ( RolePermissions::HISTORY !== $option ) { return; }
		global $wpdb;
		$other = get_option( $role_key );
		$other['subscriber']['capabilities']['emwa_concurrent_marker'] = true;
		$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $other ) ), array( 'option_name' => $role_key ) );
		wp_cache_delete( $role_key, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	};
	delete_option( RolePermissions::HISTORY );
	add_action( 'added_option', $inject );
	$result = RolePermissions::cleanup();
	remove_action( 'added_option', $inject );
	$actual = get_option( $role_key );
	$check( is_wp_error( $result ) && ! empty( $actual['subscriber']['capabilities']['emwa_concurrent_marker'] ) && ! empty( $actual['editor']['capabilities']['edit_theme_options'] ), 'a concurrent role-option update is retained and cleanup aborts instead of overwriting it' );
	wp_roles()->for_site();
	$check( true === RolePermissions::cleanup() && ! array_key_exists( 'edit_theme_options', get_role( 'editor' )->capabilities ) && ! empty( get_role( 'subscriber' )->capabilities['emwa_concurrent_marker'] ), 'retry cleans only requested legacy capabilities while retaining the concurrent grant' );
	get_role( 'editor' )->add_cap( 'edit_theme_options' );
	$protect = static function ( $new, $old ) { return $old; };
	add_filter( 'pre_update_option_' . $role_key, $protect, 10, 2 );
	$check( is_wp_error( RolePermissions::cleanup() ) && ! empty( get_role( 'editor' )->capabilities['edit_theme_options'] ), 'role-manager pre-update filters are respected by atomic cleanup' );
	remove_filter( 'pre_update_option_' . $role_key, $protect );
	update_option( Settings::HISTORY, array(), false );
	$winning_history = array( array( 'settings' => $config, 'time' => time(), 'concurrent' => true ) );
	$backup_race = static function ( $next ) use ( $winning_history ) {
		global $wpdb;
		$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $winning_history ) ), array( 'option_name' => Settings::HISTORY ) );
		return $next;
	};
	add_filter( 'pre_update_option_' . Settings::HISTORY, $backup_race );
	$result = Settings::backup( $config );
	remove_filter( 'pre_update_option_' . Settings::HISTORY, $backup_race );
	$check( is_wp_error( $result ) && $winning_history === get_option( Settings::HISTORY ), 'a concurrent settings-backup append is preserved and the stale backup fails safely' );

	$check( '' === Routes::appearance_area( 'themes.php', 'themes.php?page=third-party-theme-settings' ) && '' === Routes::appearance_area( 'themes.php', 'site-editor.php?page=third-party-design' ), 'plugin pages on core scripts are not misclassified as core Appearance screens' );
	$profile = Settings::profile( 'editor' );
	$check( ! RolePolicy::profile_hides_menu( $profile, 'themes.php', 'themes.php?page=theme-options' ) && RolePolicy::profile_hides_menu( $profile, 'themes.php', 'themes.php' ), 'hiding Themes leaves a separate theme options page visible' );
	$GLOBALS['menu'] = array( 'malformed', null, array( 'Bad' ), array( array( 'bad label' ), 'read', 'fallback-label' ), array( 'Parent', 'read', 'same-route' ) );
	$GLOBALS['submenu'] = array( 'same-route' => array( 'bad child', array( 'Overview', 'read', 'same-route' ) ) );
	$catalog = Catalog::menus();
	$check( isset( $catalog[ Routes::id( 'same-route', 'same-route' ) ] ) && count( $catalog ) === 3, 'valid self-route submenus are discovered and malformed menu entries are skipped' );
	$check( 'fallback-label' === $catalog[ Routes::id( '', 'fallback-label' ) ]['label'], 'invalid third-party labels fall back safely instead of emitting warnings' );

	$config = Settings::from_legacy( array( 'emwa_roles' => array( 'editor' => 1 ) ) );
	$config['profiles']['editor']['menus'][ Routes::id( '', 'audit-late' ) ] = array( 'parent' => '', 'slug' => 'audit-late', 'hidden' => true );
	$write( $config );
	wp_set_current_user( $editor_user->ID );
	$GLOBALS['menu'] = array();
	$GLOBALS['submenu'] = array();
	MenuVisibility::apply();
	$GLOBALS['menu'][] = array( 'Late item', 'read', 'audit-late' );
	MenuVisibility::apply();
	$check( PHP_INT_MAX === has_action( 'admin_head', array( MenuVisibility::class, 'apply' ) ) && ! in_array( 'audit-late', array_column( $GLOBALS['menu'], 2 ), true ), 'a menu registered after the final admin_menu callback is hidden before output' );

	$bad_mapper = static function ( $caps, $cap ) { if ( 'audit-throws' === $cap ) { throw new RuntimeException( 'Other plugin mapper requires an account.' ); } return $caps; };
	add_filter( 'map_meta_cap', $bad_mapper, 10, 2 );
	$items = array( 'test' => array( 'parent' => '', 'slug' => 'audit-throws', 'capability' => 'audit-throws', 'available' => true ) );
	$result = AccessPreview::menu_rules( 'editor', $config['profiles']['editor'], $items );
	remove_filter( 'map_meta_cap', $bad_mapper );
	$check( null === $result['test']['role_allowed'] && in_array( 'unknown', $result['test']['role_status'], true ), 'throwing third-party capability mappers produce unknown availability instead of breaking settings' );
	$warn_mapper = static function ( $caps, $cap ) { if ( 'audit-throws' === $cap ) { trigger_error( 'Mapper needs an account.', E_USER_WARNING ); } return $caps; };
	add_filter( 'map_meta_cap', $warn_mapper, 10, 2 );
	$result = AccessPreview::menu_rules( 'editor', $config['profiles']['editor'], $items );
	remove_filter( 'map_meta_cap', $warn_mapper );
	$check( null === $result['test']['role_allowed'], 'warning-producing capability mappers are safely bounded to the preview' );
	$invalid = SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'hidden' => array( array() ) ), array( 'Array' => array( 'parent' => '', 'slug' => 'Array' ) ) );
	$check( is_wp_error( $invalid ), 'nested checkbox input is rejected without array-to-string warnings or fatal errors' );

	wp_set_current_user( 1 );
	add_role( 'emwa.audit.Role', 'Audit role', array( 'read' => true ) );
	$_GET = array( 'tab' => 'roles', 'role' => 'editor', 'copy_target' => 'emwa.audit.Role' );
	ob_start(); SettingsPage::render(); $html = ob_get_clean();
	$check( false !== strpos( $html, 'Copy reviewed profile' ) && false !== strpos( $html, 'value="emwa.audit.Role"' ), 'copy review preserves valid custom role keys with punctuation and uppercase characters' );
	add_role( '123', 'Numeric role', array( 'read' => true ) );
	$numeric = SettingsPage::patch( $config, array( 'tab' => 'roles', 'role' => '123', 'selected_roles' => array( '123' ), 'emwa_roles_ui' => '2' ), array( 123 => 'Numeric role' ) );
	$check( ! is_wp_error( $numeric ) && ! is_wp_error( Settings::validate( $numeric ) ), 'numeric custom role identifiers survive PHP array-key coercion and can be saved' );
	$write( $numeric );
	$_GET = array( 'tab' => 'roles', 'role' => '123' );
	ob_start(); SettingsPage::render(); $html = ob_get_clean();
	$check( 1 === preg_match( '/value="123"\s+checked/', $html ) && false !== strpos( $html, 'emwa-role-card-active' ), 'a selected numeric role remains visibly selected when its form reopens' );
	update_option( Settings::HISTORY, array( array( 'settings' => $config, 'time' => array() ), 'invalid' ), false );
	update_option( RolePermissions::HISTORY, array( array( 'role' => array(), 'created' => array() ), 'invalid' ), false );
	$_GET = array( 'tab' => 'help' );
	ob_start(); SettingsPage::render(); $html = ob_get_clean();
	$check( false !== strpos( $html, 'Export settings' ), 'malformed individual backup entries do not crash Help or block exports' );

	$write( array( 'schema_version' => Settings::SCHEMA, 'roles' => array(), 'profiles' => array() ) );
	update_option( $role_key, array() );
	$GLOBALS['wp_roles'] = new WP_Roles();
	$_GET = array( 'tab' => 'roles' );
	ob_start(); SettingsPage::render(); $html = ob_get_clean();
	$check( false !== strpos( $html, 'No roles are available' ) && false !== strpos( $html, 'Export settings' ), 'a site without configurable roles retains Help and export without warnings' );
	update_option( $role_key, $original_roles );
	wp_roles()->for_site();
	wp_get_current_user()->get_role_caps();

	if ( wp_get_theme( 'twentytwentyone' )->exists() && wp_get_theme( 'twentytwentyfive' )->exists() ) {
		switch_theme( 'twentytwentyone' );
		$fresh = Settings::defaults();
		$check( RolePolicy::appearance_hidden( $fresh['profiles']['editor'], 'site_editor' ), 'classic first-install defaults hide the additional Design entry' );
		switch_theme( 'twentytwentyfive' );
		$check( ! RolePolicy::appearance_hidden( $fresh['profiles']['editor'], 'site_editor' ), 'untouched defaults expose Navigation after a later switch to a block theme' );
		$manual = SettingsPage::patch( $fresh, array( 'tab' => 'menus', 'role' => 'editor', 'emwa_appearance_ui' => '2', 'hidden_appearance' => array( 'site_editor' ), 'hidden' => array() ), array() );
		$check( RolePolicy::appearance_hidden( $manual['profiles']['editor'], 'site_editor' ), 'an explicitly saved Site Editor hide choice overrides automatic theme defaults' );
	}
} finally {
	restore_error_handler();
	if ( $created_user ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $editor_user->ID ); }
	wp_set_current_user( $original_user );
	update_option( wp_roles()->role_key, $original_roles );
	wp_roles()->for_site();
	wp_get_current_user()->get_role_caps();
	$clear();
	foreach ( $original as $option => $value ) { if ( null !== $value ) { update_option( $option, $value, false ); } }
	Settings::invalidate();
	$_GET = $original_get;
	$GLOBALS['menu'] = $original_menu;
	$GLOBALS['submenu'] = $original_submenu;
	switch_theme( $theme );
}
echo "Completed $checks audit edge-case assertions.\n";
