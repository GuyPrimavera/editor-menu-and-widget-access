<?php
/** Non-destructive migration, scoped to the current site. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class Migrations {
	private static $error;

	private static function failure() {
		self::$error = new \WP_Error( 'emwa_initialization', __( 'Editor Access settings could not be initialized safely. Existing settings and role permissions were preserved.', 'editor-menu-and-widget-access' ) );

		return self::$error;
	}

	public static function error() {
		return self::$error;
	}

	const BACKUP              = 'emwa_legacy_backup';
	const LEGACY_DEACTIVATION = 'emwa_legacy_deactivation_pending';
	const LEGACY_OPTIONS      = array( 'emwa_settings', 'emwaAdminbar', 'emwa_roles', 'emwa_role_string', 'emwa_roles_cap_added', 'emwaHelp' );

	public static function legacy_options() {
		$values = array();

		foreach ( self::LEGACY_OPTIONS as $name ) {
			$value = get_option( $name, null );

			if ( null !== $value ) {
				$values[ $name ] = $value;
			}
		}

		return $values;
	}

	/** Retained migration records distinguish reactivation/upgrades from a new install. */
	private static function first_install() {
		if ( self::legacy_options() ) {
			return false;
		}

		$backup = get_option( self::BACKUP, null );

		if ( null !== $backup ) {
			return is_array( $backup ) && ! empty( $backup['fresh_install'] );
		}

		foreach ( array( Settings::HISTORY, RolePermissions::HISTORY, RolePermissions::CLEANUP_OPTION ) as $option ) {
			if ( null !== get_option( $option, null ) ) {
				return false;
			}
		}

		return true;
	}

	public static function initial_config() {
		return self::first_install() ? Settings::defaults() : Settings::from_legacy( self::legacy_options() );
	}

	/** Preserve the original snapshot before publishing a configuration or cleaning capabilities. */
	public static function run() {
		self::$error = null;
		$current     = get_option( Settings::OPTION, null );

		if ( null !== $current && is_wp_error( Settings::validate( $current ) ) ) {
			return self::failure();
		}

		$backup = get_option( self::BACKUP, null );

		if ( null === $backup ) {
			$capabilities = array();

			foreach ( wp_roles()->roles as $role => $details ) {
				if ( ! is_array( $details ) || ! isset( $details['capabilities'] ) || ! is_array( $details['capabilities'] ) ) {
					return self::failure();
				}

				$capabilities[ $role ] = array_intersect_key( $details['capabilities'], array_flip( array( 'edit_theme_options', 'edit_themes', 'update_themes', 'delete_themes', 'install_themes', 'upload_themes', 'switch_themes' ) ) );
			}

			$backup = array(
				'schema_version' => 1,
				'fresh_install'  => null === $current && self::first_install(),
				'created'        => time(),
				'options'        => self::legacy_options(),
				'capabilities'   => $capabilities,
			);
			add_option( self::BACKUP, $backup, '', false );

			// Another request may have won; only its persisted snapshot is authoritative.
			$backup = get_option( self::BACKUP, null );
		}

		if ( ! is_array( $backup ) || ! isset( $backup['options'], $backup['capabilities'] ) || ! is_array( $backup['options'] ) || ! is_array( $backup['capabilities'] ) ) {
			return self::failure();
		}

		if ( null !== $current ) {
			return true;
		}

		$config = ! empty( $backup['fresh_install'] ) && ! $backup['options'] ? Settings::defaults() : Settings::from_legacy( $backup['options'] );

		if ( ! is_wp_error( Settings::validate( $config ) ) ) {
			add_option( Settings::OPTION, $config, '', false );
		}

		if ( ! Settings::writable() ) {
			return self::failure();
		}

		return true;
	}
}
