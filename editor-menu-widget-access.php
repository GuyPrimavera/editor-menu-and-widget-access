<?php
/**
 * Plugin Name: Editor Menu and Widget Access
 * Plugin URI: https://wordpress.org/plugins/editor-menu-and-widget-access/
 * Description: Give selected roles appearance access and simplify their admin menus and toolbar.
 * Author: Guy Primavera
 * Author URI: https://guyprimavera.com/
 * Version: 4.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 3.0.0
 * WC tested up to: 11.1
 * Text Domain: editor-menu-and-widget-access
 * Domain Path: /lang
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package EditorMenuWidgetAccess
 */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

// Private constants prevent another plugin's global EMWA_* names from changing our bootstrap.
if ( defined( __NAMESPACE__ . '\\PLUGIN_FULL_PATH' ) && constant( __NAMESPACE__ . '\\PLUGIN_FULL_PATH' ) === __FILE__ ) {
	return;
}

$emwa_namespace_conflict = defined( __NAMESPACE__ . '\\VERSION' ) || defined( __NAMESPACE__ . '\\PLUGIN_FILE' ) || defined( __NAMESPACE__ . '\\PLUGIN_FULL_PATH' ) || defined( __NAMESPACE__ . '\\PLUGIN_DIR' );
$emwa_components         = array(
	'Integrations/WooCommerce',
	'Routes',
	'Settings',
	'Migrations',
	'RolePolicy',
	'RolePermissions',
	'ProfileCopy',
	'Capabilities',
	'MenuVisibility',
	'ToolbarVisibility',
	'Admin/Catalog',
	'Admin/AccessPreview',
	'Admin/SettingsForm',
	'Admin/SettingsActions',
	'Admin/SettingsPage',
	'Plugin',
);

foreach ( $emwa_components as $emwa_component ) {
	$emwa_class = __NAMESPACE__ . '\\' . str_replace( '/', '\\', $emwa_component );

	if ( $emwa_namespace_conflict || class_exists( $emwa_class, false ) || interface_exists( $emwa_class, false ) || trait_exists( $emwa_class, false ) ) {
		add_action(
			'admin_notices',
			static function () {
				if ( current_user_can( 'manage_options' ) ) {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Editor Access could not load because another plugin uses its namespace. Check for duplicate copies of Editor Menu and Widget Access.', 'editor-menu-and-widget-access' ) . '</p></div>';
				}
			}
		);
		register_activation_hook(
			__FILE__,
			static function () {
				wp_die( esc_html__( 'Editor Access could not load because another plugin uses its namespace. Check for duplicate copies of Editor Menu and Widget Access.', 'editor-menu-and-widget-access' ), '', array( 'response' => 500 ) );
			}
		);

		return;
	}
}

define( __NAMESPACE__ . '\\VERSION', '4.0.0' );
define( __NAMESPACE__ . '\\PLUGIN_FILE', basename( __FILE__ ) );
define( __NAMESPACE__ . '\\PLUGIN_FULL_PATH', __FILE__ );
define( __NAMESPACE__ . '\\PLUGIN_DIR', __DIR__ . '/' );

// Retain historical aliases for integrations, but never depend on their values internally.
foreach ( array(
	'EMWA_VERSION'          => VERSION,
	'EMWA_PLUGIN_FILE'      => PLUGIN_FILE,
	'EMWA_PLUGIN_FULL_PATH' => PLUGIN_FULL_PATH,
	'EMWA_PLUGIN_DIR'       => PLUGIN_DIR,
) as $emwa_alias => $emwa_value ) {
	if ( ! defined( $emwa_alias ) ) {
		define( $emwa_alias, $emwa_value ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound -- Fixed, guarded EMWA-prefixed compatibility aliases.
	}
}

foreach ( $emwa_components as $emwa_component ) {
	require_once \EditorMenuWidgetAccess\PLUGIN_DIR . 'includes/' . $emwa_component . '.php';
}

\EditorMenuWidgetAccess\Plugin::boot();
register_activation_hook( __FILE__, array( \EditorMenuWidgetAccess\RolePermissions::class, 'activate' ) );
