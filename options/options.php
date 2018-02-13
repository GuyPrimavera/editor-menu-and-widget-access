<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { exit; }

//include('emwaOptionsCSS.php');
include('registerSettings.php');
include('appearanceMenu.php');
include('otherMenus.php');
include('adminbar.php');
include('help.php');

function guyPrimavera_load_style() {
    wp_register_style( 'emwa', plugins_url( 'editor-menu-and-widget-access/options/emwa.css' ) );
    wp_enqueue_style( 'emwa' );
}
add_action( 'admin_enqueue_scripts', 'guyPrimavera_load_style' );

/// Display the options page

function emwa_options_page(  ) { 

	?>
	<div class="wrap">

    <h2><?php echo __( 'Editor Menu and Widget Access Settings', 'editor-menu-and-widget-access' ); ?></h2>

    <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'adminMenus'; ?>
     
    <h2 class="nav-tab-wrapper">
        <a href="?page=editorMenuWidgetAccess&tab=adminMenus" class="dashicons-before dashicons-menu nav-tab <?php echo $active_tab == 'adminMenus' ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Admin Menu', 'editor-menu-and-widget-access' ); ?></a>
        <a href="?page=editorMenuWidgetAccess&tab=adminbar" class="dashicons-before dashicons-archive nav-tab <?php echo $active_tab == 'adminbar' ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Admin Bar', 'editor-menu-and-widget-access' ); ?></a>
        <a href="?page=editorMenuWidgetAccess&tab=help" class="dashicons-before dashicons-editor-help nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Help', 'editor-menu-and-widget-access' ); ?></a>
    </h2>

		<form action='options.php' method='post' class='emwaSettingsPage'>

	    <?php 

        if( $active_tab == 'adminMenus' ) {
          settings_fields( 'emwaPage' );
          do_settings_sections( 'emwaPage' );
          submit_button();
        } elseif ( $active_tab == 'adminbar' ) {
          settings_fields( 'emwaAdminbar' );
          do_settings_sections( 'emwaAdminbar' );
          submit_button();
        } elseif ( $active_tab == 'help' ) {
          settings_fields( 'emwaHelp' );
          do_settings_sections( 'emwaHelp' );
        } else {
          settings_fields( 'emwaPage' );
          do_settings_sections( 'emwaPage' );
        	submit_button();
        }
        //submit_button();

	    ?>


		</form>
	</div>
	<?php

}

?>