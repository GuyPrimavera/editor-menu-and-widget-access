<?php
/** Retain data by default; allow an explicit, documented permanent purge. @package EditorMenuWidgetAccess */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'EMWA_DELETE_DATA' ) || true !== EMWA_DELETE_DATA ) {
	return;
}

$emwa_options = array( 'emwa_config', 'emwa_legacy_backup', 'emwa_settings_backups', 'emwa_role_permission_backups', 'emwa_legacy_deactivation_pending', 'emwa_role_cleanup_version', 'emwa_settings', 'emwaAdminbar', 'emwa_roles', 'emwa_role_string', 'emwa_roles_cap_added', 'emwaHelp' );
$emwa_delete  = static function () use ( $emwa_options ) {
	foreach ( $emwa_options as $emwa_option ) {
		delete_option( $emwa_option );
	}
};

// Never remove role capabilities: historical ownership cannot be established.
if ( is_multisite() ) {
	$emwa_offset = 0;

	do {
		$emwa_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $emwa_offset,
			)
		);

		foreach ( $emwa_sites as $emwa_site ) {
			switch_to_blog( $emwa_site );
			$emwa_delete();
			restore_current_blog();
		}

		$emwa_offset    += 100;
		$emwa_site_count = count( $emwa_sites );
	} while ( 100 === $emwa_site_count );
} else {
	$emwa_delete();
}
