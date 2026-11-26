<?php
/** A pre-existing class in the exact namespace must not take the dashboard down. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EMWA_TEST_SITE' ) ) { throw new RuntimeException( 'Disposable test site only.' ); }
$kind = getenv( 'EMWA_COLLISION_KIND' ) ?: 'class';
if ( ! in_array( $kind, array( 'class', 'interface', 'trait', 'constant' ), true ) ) { throw new RuntimeException( 'Invalid collision fixture.' ); }
if ( 'constant' === $kind ) {
	define( 'EditorMenuWidgetAccess\\VERSION', 'foreign' );
} else {
	eval( 'namespace EditorMenuWidgetAccess; ' . $kind . ' Settings {}' );
}
require dirname( __DIR__ ) . '/editor-menu-widget-access.php';
wp_set_current_user( 1 );
ob_start();
do_action( 'admin_notices' );
$html = ob_get_clean();
if ( class_exists( 'EditorMenuWidgetAccess\\Plugin', false ) || false === strpos( $html, 'another plugin uses its namespace' ) ) {
	throw new RuntimeException( 'Exact namespace conflict was not reported safely.' );
}
echo "PASS: exact namespace collision leaves WordPress running and reports an administrator notice.\n";
