<?php
/** A fresh process verifies global aliases cannot change internal bootstrap identity. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) { throw new RuntimeException( 'Disposable test site only.' ); }
define( 'EMWA_VERSION', 'unrelated-plugin' );
define( 'EMWA_PLUGIN_FILE', 'unrelated.php' );
define( 'EMWA_PLUGIN_FULL_PATH', '/unrelated/main.php' );
define( 'EMWA_PLUGIN_DIR', '/unrelated/' );
function arrayTree() { return 'foreign'; }
function renderList() { return 'foreign'; }
wp_register_plugin_realpath( WP_PLUGIN_DIR . '/editor-menu-and-widget-access/editor-menu-widget-access.php' );
require dirname( __DIR__ ) . '/editor-menu-widget-access.php';
require dirname( __DIR__ ) . '/editor-menu-and-widget-access.php';
\EditorMenuWidgetAccess\Plugin::boot();
if ( '4.0.0' !== \EditorMenuWidgetAccess\VERSION || ! is_file( \EditorMenuWidgetAccess\PLUGIN_DIR . 'includes/Plugin.php' ) || ! class_exists( '\EditorMenuWidgetAccess\Plugin', false ) || 'unrelated-plugin' !== EMWA_VERSION || 'foreign' !== arrayTree() ) {
	throw new RuntimeException( 'Global collision or duplicate loader changed bootstrap identity.' );
}
echo "PASS: foreign globals and function names do not affect private bootstrap; both entry loaders are idempotent.\n";
