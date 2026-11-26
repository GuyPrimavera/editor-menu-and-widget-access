<?php
/** First-install defaults must not overwrite upgrades or reactivate saved profiles. @package EditorMenuWidgetAccess */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) {
	throw new RuntimeException( 'Use a disposable WordPress installation and EMWA_TEST_SITE=1.' );
}
require_once dirname( __DIR__ ) . '/editor-menu-widget-access.php';
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Routes;
use EditorMenuWidgetAccess\MenuVisibility;

$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'FAILED: ' . $message );
	}
	++$checks;
	echo "PASS: $message\n";
};
$options = array_merge( Migrations::LEGACY_OPTIONS, array( Settings::OPTION, Settings::HISTORY, Migrations::BACKUP, RolePermissions::HISTORY, RolePermissions::CLEANUP_OPTION, Migrations::LEGACY_DEACTIVATION ) );
$original = array();
foreach ( $options as $option ) {
	$original[ $option ] = get_option( $option, null );
}
$clear = static function () use ( $options ) {
	foreach ( $options as $option ) {
		delete_option( $option );
	}
	Settings::invalidate();
};
$theme = get_stylesheet();
$users = array();
try {
	$clear();
	$expected_hidden = array( 'themes', 'customize', 'identity', 'styles', 'patterns', 'templates', 'fonts' );
	$fallback = Settings::get();
	Migrations::run();
	$fresh = Settings::get();
	$check( array() === $fallback['roles'] && true === Settings::validate( $fresh ), 'uninitialized settings grant no access until safe first activation persists defaults' );
	$check( array( 'editor', 'shop_manager' ) === $fresh['roles'], 'first installation selects Editor and Shop Manager, including an absent WooCommerce role' );
	foreach ( $fresh['profiles'] as $name => $profile ) {
		$check( $profile['grant_appearance'] && empty( $profile['hidden_appearance']['menus'] ) && empty( $profile['hidden_appearance']['widgets'] ), "$name starts with the runtime grant and visible Menus/Widgets" );
		$check( ! array_diff( $expected_hidden, array_keys( array_filter( $profile['hidden_appearance'] ) ) ), "$name hides all other additional Appearance tools" );
		$check( ! $profile['restrict_customizer'] && array() === $profile['toolbar'], "$name does not introduce additional permission restrictions or hide ordinary toolbar items" );
	}
	foreach ( array( 'editor', 'shop_manager' ) as $name ) {
		if ( ! get_role( $name ) ) {
			continue;
		}
		$id = wp_insert_user( array( 'user_login' => 'emwa_defaults_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => $name ) );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		$users[] = $id;
		$user = get_userdata( $id );
		$check( user_can( $user, 'edit_theme_options' ) && ! user_can( $user, 'install_themes' ) && ! user_can( $user, 'edit_themes' ) && ! user_can( $user, 'switch_themes' ), "$name receives Appearance access without additional theme-management capabilities" );
		$check( ! RolePolicy::hides_menu( $user, 'themes.php', 'nav-menus.php' ) && ! RolePolicy::hides_menu( $user, 'themes.php', 'widgets.php' ) && in_array( 'identity', MenuVisibility::site_editor_hidden_areas( $user ), true ) && in_array( 'styles', MenuVisibility::site_editor_hidden_areas( $user ), true ), "$name can find Menus/Widgets while Identity and Styles are hidden" );
	}
	$check( true === get_option( Migrations::BACKUP )['fresh_install'], 'the original migration record identifies a genuine first install' );
	$check( ( ! wp_is_block_theme() ) === ! empty( $fresh['profiles']['editor']['hidden_appearance']['site_editor'] ), 'Site Editor entry remains available only when needed for block Navigation' );
	$edited = $fresh;
	$edited['roles'] = array();
	$edited['profiles']['editor']['hidden_appearance'] = array( 'identity' => false, 'styles' => false );
	$edited['profiles']['editor']['grant_appearance'] = false;
	update_option( Settings::OPTION, $edited, false );
	Settings::invalidate();
	RolePermissions::activate();
	$check( Settings::get() === $edited, 'an existing v4 profile and explicitly empty role selection are preserved exactly' );

	$clear();
	update_option( 'emwa_roles', array( 'editor' => 1 ), false );
	update_option( 'emwa_settings', array( 'emwa_chk_widgets' => 1 ), false );
	Migrations::run();
	$legacy = Settings::get();
	$check( array( 'editor' ) === $legacy['roles'] && ! empty( $legacy['profiles']['editor']['hidden_appearance']['widgets'] ), 'legacy selected roles and hidden Widgets migrate unchanged' );
	$check( empty( $legacy['profiles']['editor']['hidden_appearance']['identity'] ) && empty( $legacy['profiles']['editor']['hidden_appearance']['styles'] ) && empty( $legacy['profiles']['editor']['hidden_appearance']['templates'] ), 'upgrade does not add the new fresh-install restrictions' );
	$check( false === get_option( Migrations::BACKUP )['fresh_install'], 'legacy upgrade is recorded separately from first installation' );

	$clear();
	update_option( Migrations::BACKUP, array( 'options' => array(), 'capabilities' => array() ), false );
	Migrations::run();
	$check( Settings::get() === Settings::from_legacy( array() ), 'an older migration backup without live legacy options still counts as an upgrade' );
	$clear();
	update_option( RolePermissions::CLEANUP_OPTION, 1, false );
	Migrations::run();
	$check( Settings::get() === Settings::from_legacy( array() ), 'retained lifecycle data prevents existing installations from receiving new defaults' );

	foreach ( array( 'twentytwentyfive', 'twentytwentyone' ) as $stylesheet ) {
		if ( ! wp_get_theme( $stylesheet )->exists() ) {
			continue;
		}
		$clear();
		switch_theme( $stylesheet );
		Migrations::run();
		$config = Settings::get();
		$check( ( ! wp_is_block_theme() ) === ! empty( $config['profiles']['editor']['hidden_appearance']['site_editor'] ) && empty( $config['profiles']['editor']['hidden_appearance']['menus'] ) && empty( $config['profiles']['editor']['hidden_appearance']['widgets'] ), "$stylesheet initializes theme-aware Menus/Widgets defaults" );
	}
	foreach ( array(
		'site-editor.php?p=%2Fidentity' => 'identity',
		'site-editor.php?p=/styles' => 'styles',
		'site-editor.php?path=%2Fwp_global_styles' => 'styles',
		'site-editor.php?p=/stylebook' => 'styles',
		'site-editor.php?p=/template' => 'templates',
		'site-editor.php?postType=wp_template' => 'templates',
		'site-editor.php?p=%2Fnavigation' => 'menus',
		'site-editor.php?path=/patterns' => 'patterns',
	) as $route => $area ) {
		$check( $area === Routes::appearance_area( 'themes.php', $route ), "Site Editor route maps to $area: $route" );
	}
	$input = array( 'tab' => 'menus', 'role' => 'editor', 'emwa_appearance_ui' => '2', 'hidden_appearance' => array( 'identity', 'styles', 'templates' ), 'hidden' => array() );
	$patched = \EditorMenuWidgetAccess\Admin\SettingsPage::patch( $config, $input, array() );
	$check( ! is_wp_error( $patched ) && array( 'identity' => true, 'styles' => true, 'templates' => true ) === $patched['profiles']['editor']['hidden_appearance'], 'the new Identity, Styles and Templates options survive settings validation and saving' );
} finally {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $id ) {
		wp_delete_user( $id );
	}
	switch_theme( $theme );
	$clear();
	foreach ( $original as $option => $value ) {
		if ( null !== $value ) {
			update_option( $option, $value, false );
		}
	}
	Settings::invalidate();
}
echo "Completed $checks first-install and visibility assertions.\n";
