<?php
/**
 * Real WordPress integration checks, intended ONLY for a disposable test site.
 * Run: EMWA_TEST_SITE=1 wp eval-file tests/integration.php --skip-plugins --skip-themes
 * @package EditorMenuWidgetAccess
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable WordPress installation and set EMWA_TEST_SITE=1.' );
}
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Capabilities;
use EditorMenuWidgetAccess\MenuVisibility;
use EditorMenuWidgetAccess\ToolbarVisibility;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\Admin\SettingsPage;
use EditorMenuWidgetAccess\Admin\Catalog;

$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'FAILED: ' . $message );
	}
	++$checks;
	echo "PASS: $message\n";
};
$write = static function ( $config ) {
	update_option( Settings::OPTION, $config, false );
	Settings::invalidate();
};
$clear = static function () {
	foreach ( array_merge( Migrations::LEGACY_OPTIONS, array( Settings::OPTION, Settings::HISTORY, Migrations::BACKUP, Migrations::LEGACY_DEACTIVATION ) ) as $option ) {
		delete_option( $option );
	}
	Settings::invalidate();
};
$clear();
$roles_before = get_option( wp_roles()->role_key );
$legacy = array(
	'emwa_settings' => array( 'emwa_chk_custom' => 0, 'emwa_chk_widgets' => 1, 'edit.php?post_type=book&scope[]=public' => 1, 'settings.php?page=gone-plugin' => 1, 'vc-general' => 1, 42 => 1 ),
	'emwaAdminbar' => array( 'wp-logo' => 1, 'missing-toolbar-node' => 0, 123 => 1 ),
	'emwa_roles' => array( 'editor' => 1, 'shop_manager' => 1, 'deleted_role' => 1 ),
	'emwa_role_string' => 'A legacy translated label',
);
foreach ( $legacy as $key => $value ) { update_option( $key, $value ); }
Migrations::run();
$config = Settings::get();
$check( ! is_wp_error( Settings::validate( $config ) ), 'legacy configuration migrates into a valid schema' );
$check( Migrations::legacy_options() === $legacy, 'every original legacy option remains unchanged' );
$check( get_option( Migrations::BACKUP )['options'] === $legacy, 'exact legacy backup exists before migration' );
$check( get_option( wp_roles()->role_key ) === $roles_before, 'migration does not change stored roles' );
$check( false === get_option( Migrations::LEGACY_DEACTIVATION, false ), 'settings migration no longer schedules deactivation-time role mutations' );
$check( $config['profiles']['editor']['hidden_appearance']['customize'], 'legacy zero-valued checked flag is retained' );
$check( isset( $config['profiles']['editor']['toolbar']['missing-toolbar-node'] ), 'unavailable toolbar rule survives' );
$check( RolePolicy::profile_hides_menu( $config['profiles']['editor'], 'plugin', '42' ) && ! empty( $config['profiles']['editor']['toolbar'][123] ), 'numeric legacy route and toolbar IDs survive migration' );
$check( ! is_wp_error( Settings::validate( $config ) ), 'numeric legacy IDs remain valid after storage' );
$check( array( 'editor', 'shop_manager' ) === Settings::from_legacy( array( 'emwa_roles' => array() ) )['roles'], 'empty legacy role selection retains version 3 defaults' );
$check( RolePolicy::profile_hides_menu( $config['profiles']['editor'], 'books.php', 'edit.php?post_type=book&scope[]=public' ), 'route punctuation and brackets survive migration' );
$check( RolePolicy::profile_hides_menu( $config['profiles']['editor'], '', 'vc-welcome' ), 'historical WPBakery alias survives' );
$backup = get_option( Migrations::BACKUP );
Migrations::run();
$check( get_option( Migrations::BACKUP ) === $backup && Settings::get() === $config, 'migration is idempotent' );
$future = array( 'schema_version' => 99, 'roles' => array(), 'profiles' => array(), 'future_data' => 'keep' );
$write( $future );
Migrations::run();
$check( get_option( Settings::OPTION ) === $future && ! Settings::writable(), 'future-version data is never downgraded' );
$check( array() === Settings::get()['roles'], 'unsupported settings add no new permissions' );
$write( 'corrupted but retained' );
Migrations::run();
$check( 'corrupted but retained' === get_option( Settings::OPTION ), 'malformed settings are never silently replaced' );
$write( $config );

$editor_id = wp_insert_user( array( 'user_login' => 'emwa_editor_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
$admin_id = wp_insert_user( array( 'user_login' => 'emwa_admin_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'administrator' ) );
$editor = new WP_User( $editor_id );
$editor->add_role( 'subscriber' );
wp_set_current_user( $editor_id );
$check( count( RolePolicy::profiles( wp_get_current_user() ) ) === 1, 'all roles are considered, including Editor followed by Subscriber' );
$check( RolePolicy::hides_menu( wp_get_current_user(), 'themes.php', 'customize.php?return=another-screen' ), 'volatile Customizer return URL does not change identity' );
$components = MenuVisibility::customizer_components( array( 'widgets', 'nav_menus' ) );
$check( ! in_array( 'widgets', $components, true ), 'Customizer component at index zero is hidden' );
$check( array() === MenuVisibility::customizer_components( array( 'widgets' ) ), 'widgets filter does not require nav_menus to exist' );
$GLOBALS['menu'] = array( 60 => array( 'Appearance', 'edit_theme_options', 'themes.php' ) );
$GLOBALS['submenu'] = array( 'themes.php' => array( 6 => array( 'Design', 'edit_theme_options', 'site-editor.php' ), 7 => array( 'Customize', 'customize', 'customize.php?return=screen' ), 10 => array( 'Menus', 'edit_theme_options', 'nav-menus.php' ), 9 => array( 'Fonts', 'edit_theme_options', 'font-library.php' ), 11 => array( 'Custom Layouts', 'edit_theme_options', 'edit.php?post_type=astra-advanced-hook' ) ) );
$astra_slug = 'edit.php?post_type=astra-advanced-hook';
$check( isset( Catalog::menus()[ Routes::id( 'themes.php', $astra_slug ) ] ), 'third-party Appearance submenus are available in the catalog' );
$config['profiles']['editor']['menus'][ Routes::id( 'themes.php', $astra_slug ) ] = array( 'parent' => 'themes.php', 'slug' => $astra_slug, 'hidden' => true );
$write( $config );
MenuVisibility::apply();
$remaining = array_column( $GLOBALS['submenu']['themes.php'], 2 );
$check( ! in_array( 'customize.php?return=screen', $remaining, true ) && in_array( 'nav-menus.php', $remaining, true ), 'current submenu positions are filtered by route' );
$check( ! in_array( $astra_slug, $remaining, true ), 'third-party Appearance submenu is hidden by its exact route' );
$check( in_array( 'font-library.php', $remaining, true ), 'unconfigured new appearance screens remain intact' );

$profile = Settings::profile( 'editor' );
$profile['menus'][ Routes::id( '*', 'shared-child' ) ] = array( 'parent' => '*', 'slug' => 'shared-child', 'hidden' => true );
$profile['menus'][ Routes::id( 'parent-a', 'shared-child' ) ] = array( 'parent' => 'parent-a', 'slug' => 'shared-child', 'hidden' => false );
$check( ! RolePolicy::profile_hides_menu( $profile, 'parent-a', 'shared-child' ) && RolePolicy::profile_hides_menu( $profile, 'parent-b', 'shared-child' ), 'per-parent allow overrides a legacy wildcard without affecting another parent' );
$profile['menus'][ Routes::id( '*', 'edit.php?post_type=shop_order' ) ] = array( 'parent' => '*', 'slug' => 'edit.php?post_type=shop_order', 'hidden' => true );
$check( RolePolicy::profile_hides_menu( $profile, 'woocommerce', 'wc-orders' ), 'order rule survives switching to HPOS menus' );

$check( ! isset( Capabilities::grant( array( 'edit_theme_options' => false ), array( 'edit_theme_options' ), array(), $editor )['edit_theme_options'] ) || false === Capabilities::grant( array( 'edit_theme_options' => false ), array( 'edit_theme_options' ), array(), $editor )['edit_theme_options'], 'explicit denial is never overwritten' );
$granted = Capabilities::grant( array(), array( 'edit_theme_options' ), array(), $editor );
$check( ! empty( $granted['edit_theme_options'] ), 'selected role receives an additive runtime grant' );
$check( array() === Capabilities::grant( array(), array( 'install_themes' ), array(), $editor ), 'unrelated capabilities are not granted' );
$check( array() === RolePolicy::profiles( get_userdata( $admin_id ) ), 'administrator is exempt from all policies' );
$check( get_option( wp_roles()->role_key ) === $roles_before, 'request handling leaves stored role capabilities unchanged' );
$rest_post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'EMWA REST draft', 'post_author' => $editor_id ) );
$rest_save = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $rest_post_id );
$rest_save->set_param( 'title', 'EMWA REST edited' );
$rest_response = rest_do_request( $rest_save );
$check( 200 === $rest_response->get_status() && 'EMWA REST edited' === get_post( $rest_post_id )->post_title, 'Editor can still save their own post through core REST API' );
$site_title = get_option( 'blogname' );
$rest_settings = new WP_REST_Request( 'POST', '/wp/v2/settings' );
$rest_settings->set_param( 'title', 'Unauthorized change' );
$rest_response = rest_do_request( $rest_settings );
$check( 403 === $rest_response->get_status() && $site_title === get_option( 'blogname' ), 'appearance grant does not authorize administrator REST settings changes' );

$config['profiles']['editor']['restrict_customizer'] = true;
$write( $config );
$check( ! current_user_can( 'customize' ), 'opt-in restriction denies the core Customizer capability' );
$check( current_user_can( 'edit_theme_options' ), 'Customizer restriction leaves widgets and other appearance access intact' );
$post_id = wp_insert_post( array( 'post_type' => 'customize_changeset', 'post_status' => 'draft', 'post_title' => 'EMWA restriction test', 'post_author' => $editor_id ) );
$check( ! current_user_can( 'edit_post', $post_id ), 'Customizer changeset save permissions are also denied' );
$config['profiles']['editor']['restrict_customizer'] = false;
$write( $config );
$check( current_user_can( 'customize' ), 'Customizer permissions recover when restriction is disabled' );

require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
$GLOBALS['wp_admin_bar'] = new WP_Admin_Bar();
$GLOBALS['wp_admin_bar']->add_node( array( 'id' => 'wp-logo', 'title' => 'Logo' ) );
$GLOBALS['wp_admin_bar']->add_node( array( 'id' => 'late-node', 'title' => 'Late node' ) );
$GLOBALS['wp_admin_bar']->add_node( array( 'id' => 'edit', 'title' => 'Edit Page' ) );
$GLOBALS['wp_admin_bar']->add_node( array( 'id' => 'theme-customizer', 'title' => 'Theme Customizer', 'href' => admin_url( 'customize.php?autofocus[control]=header_image' ) ) );
$GLOBALS['wp_admin_bar']->add_node( array( 'id' => 'external-customizer', 'title' => 'External link', 'href' => 'https://example.org/wp-admin/customize.php' ) );
$config['profiles']['editor']['toolbar']['late-node'] = true;
$config['profiles']['editor']['toolbar']['edit'] = true;
$write( $config );
ToolbarVisibility::apply();
$check( ! $GLOBALS['wp_admin_bar']->get_node( 'late-node' ), 'configured late-added toolbar ID is removed directly' );
$check( ! $GLOBALS['wp_admin_bar']->get_node( 'edit' ) && isset( Catalog::toolbar_rules( $config['profiles']['editor'] )['edit'] ), 'frontend Edit link can be configured even when absent from the administrator screen' );
$check( ! $GLOBALS['wp_admin_bar']->get_node( 'theme-customizer' ), 'theme-added Customizer toolbar link follows the Appearance setting' );
$check( $GLOBALS['wp_admin_bar']->get_node( 'external-customizer' ), 'external links are not mistaken for this site’s Customizer' );
$check( isset( Catalog::toolbar_rules( $config['profiles']['editor'] )['missing-toolbar-node'] ), 'UI retains unavailable toolbar rules' );

$revision = Settings::revision( $config );
$items = array( 'm_test' => array( 'parent' => 'plugin-parent', 'slug' => 'plugin-child', 'managed' => false ) );
$items = array( Routes::id( 'plugin-parent', 'plugin-child' ) => $items['m_test'] );
$next = SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'hidden' => array_keys( $items ) ), $items );
$check( $next['profiles']['shop_manager'] === $config['profiles']['shop_manager'], 'editing one role retains all other profiles' );
$check( $next['profiles']['editor']['toolbar'] === $config['profiles']['editor']['toolbar'], 'editing one tab retains other tabs' );
$check( isset( $next['profiles']['editor']['menus'][ Routes::id( '*', 'settings.php?page=gone-plugin' ) ] ), 'partial save retains a rule for an absent plugin' );
$check( $next['profiles']['editor']['hidden_appearance'] === $config['profiles']['editor']['hidden_appearance'], 'older menu form preserves Appearance settings' );
$new_menu = SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'emwa_appearance_ui' => '2', 'hidden_appearance' => array( 'widgets' ), 'hidden' => array() ), $items );
$check( array( 'widgets' => true ) === $new_menu['profiles']['editor']['hidden_appearance'], 'new menu form saves Appearance links with other menu rules' );
$check( is_wp_error( SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'emwa_appearance_ui' => '2', 'hidden_appearance' => array( 'invalid' ) ), $items ) ), 'invalid Appearance selection is rejected' );
$check( true === Settings::save( $next, $revision ), 'revision-checked save succeeds' );
$check( get_option( Settings::HISTORY )[0]['settings'] === $config, 'pre-save backup contains the complete previous configuration' );
$check( is_wp_error( Settings::save( $config, $revision ) ) && Settings::get() === $next, 'stale form is rejected without overwriting a newer save' );
$latest = Settings::get();
$race = $latest;
$race['race_winner'] = true;
global $wpdb;
$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $race ) ), array( 'option_name' => Settings::OPTION ) );
$loser = $latest;
$loser['race_loser'] = true;
$check( is_wp_error( Settings::save( $loser, Settings::revision( $latest ) ) ), 'atomic comparison catches a race after the cached read' );
Settings::invalidate();
$check( Settings::get() === $race, 'concurrent winner remains stored' );
$write( $config );

$empty = SettingsPage::patch( $config, array( 'tab' => 'roles', 'role' => 'editor', 'selected_roles' => array(), 'hidden_appearance' => array() ), array_fill_keys( array_keys( $config['profiles'] ), 'Role' ) );
$check( array() === $empty['roles'] && isset( $empty['profiles']['shop_manager'] ), 'empty selection disables profiles without deleting them' );
$new_roles = SettingsPage::patch( $config, array( 'tab' => 'roles', 'role' => 'editor', 'emwa_roles_ui' => '2', 'selected_roles' => array( 'editor' ) ), array_fill_keys( array_keys( $config['profiles'] ), 'Role' ) );
$check( $new_roles['profiles']['editor']['hidden_appearance'] === $config['profiles']['editor']['hidden_appearance'], 'new role form does not change Appearance link visibility' );
$write( $empty );
$check( array() === RolePolicy::profiles( $editor ), 'empty selection does not revert to legacy defaults' );
$write( $config );
$reset = SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'intent' => 'reset' ), array() );
$check( array() === $reset['profiles']['editor']['menus'] && $reset['profiles']['editor']['toolbar'] === $config['profiles']['editor']['toolbar'], 'reset affects only the selected role and tab' );
$menu_reset = SettingsPage::patch( $config, array( 'tab' => 'menus', 'role' => 'editor', 'emwa_appearance_ui' => '2', 'intent' => 'reset' ), array() );
$check( $menu_reset['profiles']['editor']['hidden_appearance'] === Settings::profile( 'editor' )['hidden_appearance'] && $menu_reset['profiles']['editor']['grant_appearance'] === $config['profiles']['editor']['grant_appearance'], 'new menu reset includes Appearance links but preserves grants' );

$manifest = SettingsPage::manifest( 'menus', 'editor', $items );
$check( $items === SettingsPage::decode_manifest( $manifest[0], $manifest[1], 'menus', 'editor' ), 'signed manifest round-trips' );
$check( is_wp_error( SettingsPage::decode_manifest( $manifest[0] . 'x', $manifest[1], 'menus', 'editor' ) ), 'tampered manifest is rejected' );
$check( is_wp_error( SettingsPage::decode_manifest( $manifest[0], $manifest[1], 'toolbar', 'editor' ) ), 'manifest cannot be replayed on another tab' );
$entity_items = array( 'test' => array( 'slug' => 'admin.php?page=one&amp;view=two' ) );
$entity_manifest = SettingsPage::manifest( 'menus', 'editor', $entity_items );
$check( $entity_items === SettingsPage::decode_manifest( $entity_manifest[0], $entity_manifest[1], 'menus', 'editor' ), 'signed manifest preserves HTML entities in plugin menu slugs' );
$import = Settings::import( wp_json_encode( Settings::export( $config ) ), $empty );
$check( ! is_wp_error( $import ) && $import['profiles'] === $config['profiles'], 'validated export/import preserves all profiles' );
$check( is_wp_error( Settings::import( '{"plugin":"other-plugin"}', $config ) ), 'foreign import is rejected' );
$check( is_wp_error( Settings::import( wp_json_encode( Settings::export( $future ) ), $config ) ), 'future schema import is rejected' );
$check( is_wp_error( Settings::import( 'broken JSON', $config ) ), 'malformed JSON import is rejected' );

for ( $i = 0; $i < 12; ++$i ) {
	$next = Settings::get();
	$next['sequence'] = $i;
	$check( true === Settings::save( $next, Settings::revision( Settings::get() ) ), 'backup history save ' . $i );
}
$check( 10 === count( get_option( Settings::HISTORY ) ), 'backup history is bounded at ten configurations' );
$before = Settings::get();
update_option( Settings::HISTORY, 'unreadable history' );
$next = $before;
$next['sequence'] = 99;
$check( is_wp_error( Settings::save( $next, Settings::revision( $before ) ) ) && Settings::get() === $before, 'backup failure prevents settings changes' );
$check( Migrations::legacy_options() === $legacy, 'legacy options remain byte-equivalent after saves and imports' );
$check( get_option( Migrations::BACKUP ) === $backup, 'original migration backup is immutable' );
$check( get_option( wp_roles()->role_key ) === $roles_before, 'stored role capabilities are unchanged after the entire suite' );
$before_uninstall = get_option( Settings::OPTION );
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', plugin_basename( EMWA_PLUGIN_FULL_PATH ) );
}
require dirname( __DIR__ ) . '/uninstall.php';
$check( get_option( Settings::OPTION ) === $before_uninstall && get_option( Migrations::BACKUP ) === $backup, 'ordinary uninstall retains configuration and original migration backup' );
$clear();
Migrations::run();
echo "Completed $checks integration assertions.\n";
