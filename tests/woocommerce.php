<?php
/** WooCommerce checks on a disposable site. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable site and EMWA_TEST_SITE=1.' );
}
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\RolePermissions;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
if ( ! defined( 'WC_VERSION' ) || ! class_exists( Settings::class ) ) {
	throw new RuntimeException( 'Activate WooCommerce and Editor Access first.' );
}
$plugin = plugin_basename( EMWA_PLUGIN_FULL_PATH );
$features = wc_get_container()->get( FeaturesController::class );
$compatibility = $features->get_compatible_plugins_for_feature( 'custom_order_tables' );
if ( ! in_array( $plugin, $compatibility['compatible'], true ) ) {
	throw new RuntimeException( 'HPOS declaration was not registered for the actual entry point.' );
}
echo 'PASS: HPOS compatibility declared for ' . $plugin . "\n";
$config = Settings::get();
$config['roles'] = array( 'editor', 'shop_manager' );
$config['profiles']['shop_manager'] = Settings::profile( 'shop_manager' );
$config['profiles']['shop_manager']['menus'][ Routes::id( '*', 'edit.php?post_type=shop_order' ) ] = array( 'parent' => '*', 'slug' => 'edit.php?post_type=shop_order', 'hidden' => true );
update_option( Settings::OPTION, $config, false );
$role_option = get_option( wp_roles()->role_key );
foreach ( array( array( 'no', 'no' ), array( 'yes', 'no' ), array( 'yes', 'yes' ) ) as $mode ) {
	update_option( 'woocommerce_custom_orders_table_enabled', $mode[0] );
	update_option( 'woocommerce_custom_orders_table_data_sync_enabled', $mode[1] );
	if ( ( 'yes' === $mode[0] ) !== \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) { throw new RuntimeException( 'Requested storage engine was not enabled.' ); }
	$store = WC_Data_Store::load( 'order' )->get_current_class_name();
	if ( ( 'yes' === $mode[0] ) !== ( false !== strpos( $store, 'OrdersTableDataStore' ) ) ) { throw new RuntimeException( 'Unexpected order data store: ' . $store ); }
	$profile = Settings::get()['profiles']['shop_manager'];
	if ( ! RolePolicy::profile_hides_menu( $profile, 'woocommerce', 'wc-orders' ) || ! RolePolicy::profile_hides_menu( $profile, 'woocommerce', 'edit.php?post_type=shop_order' ) ) {
		throw new RuntimeException( 'Order visibility was lost when switching storage modes.' );
	}
	$order = wc_create_order();
	$order->set_status( 'pending' );
	$order->save();
	$loaded = wc_get_order( $order->get_id() );
	if ( ! $loaded || $loaded->get_status() !== 'pending' ) {
		throw new RuntimeException( 'WooCommerce CRUD failed during compatibility check.' );
	}
	$order->delete( true );
	echo "PASS: WooCommerce order CRUD and visibility; HPOS {$mode[0]}, sync {$mode[1]}\n";
}
$shop_id = wp_insert_user( array( 'user_login' => 'emwa_shop_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'shop_manager' ) );
wp_set_current_user( $shop_id );
if ( ! current_user_can( 'edit_theme_options' ) || ! RolePolicy::hides_menu( wp_get_current_user(), 'woocommerce', 'wc-orders' ) ) {
	throw new RuntimeException( 'Shop Manager policy failed.' );
}
if ( get_option( wp_roles()->role_key ) !== $role_option ) {
	throw new RuntimeException( 'Role capabilities were mutated.' );
}
echo "PASS: Shop Manager access without stored role changes; WooCommerce " . WC_VERSION . "\n";
$shop_role = get_role( 'shop_manager' );
$shop_caps = $shop_role->capabilities;
$reset = RolePermissions::reset( 'shop_manager', Settings::revision( Settings::get() ), RolePermissions::revision( $shop_role ) );
if ( is_wp_error( $reset ) || get_role( 'shop_manager' )->capabilities !== $shop_caps || ! current_user_can( 'edit_theme_options' ) || Settings::get()['profiles']['shop_manager']['grant_appearance'] ) {
	throw new RuntimeException( 'Shop Manager reset changed WooCommerce-owned permissions.' );
}
echo "PASS: Shop Manager reset preserves WooCommerce role defaults\n";
update_option( 'woocommerce_custom_orders_table_enabled', 'no' );
update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
