<?php
/** Test-only fixtures for menu identity and late registration. */
add_action( 'admin_menu', static function () {
	foreach ( array( 'fixture-alpha' => 'Fixture Alpha', 'fixture-beta' => 'Fixture Beta' ) as $slug => $label ) {
		add_menu_page( $label, $label, 'edit_posts', $slug, static function () {} );
		add_submenu_page( $slug, 'Shared fixture child', 'Shared fixture child', 'edit_posts', 'fixture-child&path=/products&ids[]=one', static function () {} );
	}
}, 99999 );
add_action( 'admin_bar_menu', static function ( $bar ) {
	$bar->add_node( array( 'id' => 'emwa-late-fixture', 'title' => 'Late fixture toolbar node' ) );
}, PHP_INT_MAX );

// Registered after the plugin's callback, even at the same maximum priority.
add_action( 'plugins_loaded', static function () {
	add_action( 'admin_menu', static function () {
		add_menu_page( 'Delayed menu', 'Delayed menu', 'edit_posts', 'emwa-delayed-menu', static function () {} );
	}, PHP_INT_MAX );
} );
