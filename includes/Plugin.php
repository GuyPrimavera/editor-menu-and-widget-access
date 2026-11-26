<?php
/** Plugin composition without a runtime framework. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

use EditorMenuWidgetAccess\Admin\SettingsPage;
use EditorMenuWidgetAccess\Admin\Catalog;
use EditorMenuWidgetAccess\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public static function boot() {
		add_action( 'init', array( self::class, 'translations' ), -1 );

		if ( did_action( 'init' ) ) {
			self::translations();
		}

		foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $hook ) {
			add_action( $hook, array( Settings::class, 'invalidate' ) );
		}

		add_action( 'switch_blog', array( Settings::class, 'invalidate' ), 10, 0 );
		add_action( 'init', array( Migrations::class, 'run' ), 0 );
		add_action( 'init', array( RolePermissions::class, 'maybe_cleanup' ), 1 );
		add_action( 'admin_notices', array( RolePermissions::class, 'notice' ) );

		add_filter( 'user_has_cap', array( Capabilities::class, 'grant' ), 20, 4 );
		add_filter( 'map_meta_cap', array( Capabilities::class, 'restrict' ), 20, 3 );

		add_action( 'admin_menu', array( MenuVisibility::class, 'apply' ), PHP_INT_MAX );
		add_action( 'admin_head', array( MenuVisibility::class, 'apply' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( MenuVisibility::class, 'site_editor_assets' ) );
		add_filter( 'admin_body_class', array( MenuVisibility::class, 'site_editor_classes' ) );
		add_filter( 'customize_loaded_components', array( MenuVisibility::class, 'customizer_components' ) );

		add_action( 'wp_before_admin_bar_render', array( ToolbarVisibility::class, 'apply' ), PHP_INT_MAX );
		add_action( 'wp_before_admin_bar_render', array( Catalog::class, 'capture_toolbar' ), PHP_INT_MAX );

		add_action( 'before_woocommerce_init', array( WooCommerce::class, 'declare_compatibility' ) );

		if ( is_admin() ) {
			SettingsPage::register_hooks();
		}
	}

	public static function translations() {
		load_plugin_textdomain( 'editor-menu-and-widget-access', false, dirname( plugin_basename( \EditorMenuWidgetAccess\PLUGIN_FULL_PATH ) ) . '/lang' ); // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Required for bundled catalogs on the supported WordPress 6.2 minimum, without a downloaded language pack.
	}
}
