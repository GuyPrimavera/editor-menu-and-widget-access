<?php
/** Optional WooCommerce integration. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess\Integrations;

defined( 'ABSPATH' ) || exit;

final class WooCommerce {
	/** Declare only features that this plugin actually supports. */
	public static function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', \EditorMenuWidgetAccess\PLUGIN_FULL_PATH, true );
		}
	}

	/** Keep order visibility rules when a store switches storage engines. */
	public static function canonical_menu( $route ) {
		if ( in_array( $route, array( 'edit.php?post_type=shop_order', 'admin.php?page=wc-orders', 'wc-orders' ), true ) ) {
			return 'wc-orders';
		}

		return $route;
	}
}
