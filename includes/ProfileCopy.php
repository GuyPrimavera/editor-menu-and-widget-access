<?php
/** Revision-checked copying of one saved role profile. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class ProfileCopy {
	public static function copy( $source, $target, $revision ) {
		if ( ! is_string( $source ) || ! is_string( $target ) || $source === $target || ! Routes::valid_string( $source, 191 ) || ! Routes::valid_string( $target, 191 ) ) {
			return new \WP_Error( 'emwa_copy', __( 'Choose two different available roles.', 'editor-menu-and-widget-access' ) );
		}

		$source_role = get_role( $source );
		$target_role = get_role( $target );

		if ( ! $source_role || ! $target_role || 'administrator' === $source || 'administrator' === $target || ! empty( $source_role->capabilities['manage_options'] ) || ! empty( $target_role->capabilities['manage_options'] ) ) {
			return new \WP_Error( 'emwa_copy', __( 'A role is unavailable or has administrator permissions.', 'editor-menu-and-widget-access' ) );
		}

		$current = Settings::get();

		if ( ! Settings::writable() || ! isset( $current['profiles'][ $source ] ) || ! is_string( $revision ) || ! hash_equals( Settings::revision( $current ), $revision ) ) {
			return new \WP_Error( 'emwa_stale', __( 'Settings changed since this review opened. Reload and review the copy again; nothing was changed.', 'editor-menu-and-widget-access' ) );
		}

		$next                        = $current;
		$next['profiles'][ $target ] = $current['profiles'][ $source ];

		return Settings::save( $next, $revision );
	}
}
