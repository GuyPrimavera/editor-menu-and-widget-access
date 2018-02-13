<?php 
if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

$emwaOptions = get_option( 'emwa_settings' );

// Allow Editors access to the Appearance menu

function emwa_add_cap( $caps ) {

	if( ! empty( $caps[ 'edit_pages' ] ) ) {
		$caps[ 'edit_theme_options' ] = true;
	}
	return $caps;
	
}
add_filter( 'user_has_cap', 'emwa_add_cap' );


// Remove access to Themes page.

function emwa_set_capabilities() {

    $editor = get_role( 'editor' );
    $shop_man = get_role( 'shop_manager' );

    $caps = array(
        'edit_themes',
        'update_themes',
        'delete_themes',
        'install_themes',
        'upload_themes'
    );

    foreach ( $caps as $cap ) {
    
        // Remove the capability.
        $editor->remove_cap( $cap );
        $shop_man->remove_cap( $cap );
    }
}
add_action( 'init', 'emwa_set_capabilities' );

?>