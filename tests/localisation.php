<?php
/** Exercise translated templates against real WordPress locales on a disposable site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) { throw new RuntimeException( 'Disposable WordPress only.' ); }
wp_register_plugin_realpath( WP_PLUGIN_DIR . '/editor-menu-and-widget-access/editor-menu-widget-access.php' );
require_once WP_PLUGIN_DIR . '/editor-menu-and-widget-access/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\Admin\SettingsPage;
$options = array( Settings::OPTION, Settings::HISTORY, RolePermissions::HISTORY, 'date_format', 'time_format', 'timezone_string', 'gmt_offset' );
$before = array();
foreach ( $options as $key ) { $before[ $key ] = get_option( $key, null ); }
$original_get = $_GET;
$original_menu = $GLOBALS['menu'] ?? array();
$original_submenu = $GLOBALS['submenu'] ?? array();
$original_user = get_current_user_id();
$count = 0;
$check = static function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( 'FAILED: ' . $message ); } ++$count; echo "PASS: $message\n"; };
$render = static function ( $tab ) {
	$_GET = array( 'tab' => $tab, 'role' => 'editor', 'show_all' => '1', 'copy_target' => 'author' );
	ob_start();
	try { SettingsPage::render(); return ob_get_contents(); } finally { ob_end_clean(); }
};
$timestamp = 1735739400; // 1 January 2025, 13:50 UTC; dates below cross the day boundary.
try {
	wp_set_current_user( 1 );
	$GLOBALS['menu'] = array( array( 'Édition', 'edit_posts', 'edition' ) );
	$GLOBALS['submenu'] = array();
	$config = Settings::from_legacy( array( 'emwa_roles' => array( 'editor' => 1 ) ) );
	for ( $i = 0; $i < 1000; ++$i ) {
		$slug = 'unavailable-' . $i;
		$config['profiles']['editor']['menus'][ Routes::id( '', $slug ) ] = array( 'parent' => '', 'slug' => $slug, 'hidden' => true );
	}
	$config['profiles']['editor']['toolbar'] = array_fill_keys( range( 1, 1000 ), true );
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
	$html = $render( 'menus' );
	$check( false !== strpos( $html, '1,000 saved rules refer to unavailable items.' ), 'English grouped counts retain all digits instead of truncating to 1' );
	$check( false !== strpos( $html, 'data-emwa-search="Édition edition"' ), 'menu search preserves the original Unicode label for browser-side normalization' );
	$html = $render( 'roles' );
	$check( 2 === substr_count( $html, '>1,000</td>' ), 'profile-copy menu and toolbar summaries use localized grouped numbers' );
	if ( ! switch_to_locale( 'it_IT' ) ) { throw new RuntimeException( 'Install the it_IT core language on this disposable site.' ); }
	try {
		$html = $render( 'menus' );
		$check( false !== strpos( $html, '1.000 regole salvate si riferiscono a elementi non disponibili.' ), 'Italian bundled plural translation preserves the localized thousands separator' );
		$html = $render( 'roles' );
		$check( 2 === substr_count( $html, '>1.000</td>' ), 'Italian profile-copy summaries use the active locale' );
		$config['profiles']['editor']['menus'] = array_slice( $config['profiles']['editor']['menus'], 0, 1, true );
		update_option( Settings::OPTION, $config, false );
		Settings::invalidate();
		$html = $render( 'menus' );
		$check( false !== strpos( $html, '1 regola salvata si riferisce a un elemento non disponibile.' ), 'singular translation still selects the correct grammatical form' );
	} finally { restore_previous_locale(); }
	update_option( Settings::HISTORY, array( array( 'time' => $timestamp, 'settings' => $config ) ), false );
	update_option( RolePermissions::HISTORY, array( array( 'role' => 'editor', 'created' => $timestamp, 'capabilities' => array() ) ), false );
	update_option( 'date_format', 'd/m/Y' );
	update_option( 'time_format', 'H:i' );
	update_option( 'timezone_string', 'Pacific/Auckland' );
	$html = $render( 'help' );
	$check( 2 === substr_count( $html, '02/01/2025 02:50' ), 'both backup lists respect date/time preferences and the site timezone across a day boundary' );
	update_option( 'date_format', 'F j, Y' );
	update_option( 'time_format', 'g:i a' );
	update_option( 'timezone_string', 'UTC' );
	$html = $render( 'help' );
	$check( 2 === substr_count( $html, 'January 1, 2025 1:50 pm' ), 'changing date/time preferences updates both backup lists without changing stored timestamps' );
	$check( 2 === preg_match_all( '/<textarea[^>]+dir="ltr"/', $html ), 'JSON input and diagnostics explicitly preserve left-to-right technical content in RTL locales' );
} finally {
	foreach ( $before as $key => $value ) { if ( null === $value ) { delete_option( $key ); } else { update_option( $key, $value, false ); } }
	Settings::invalidate();
	$_GET = $original_get;
	$GLOBALS['menu'] = $original_menu;
	$GLOBALS['submenu'] = $original_submenu;
	wp_set_current_user( $original_user );
}
echo "Completed $count localisation assertions.\n";
