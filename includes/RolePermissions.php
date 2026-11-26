<?php
/** Explicit, per-role cleanup of historical appearance permissions. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class RolePermissions {
	const HISTORY         = 'emwa_role_permission_backups';
	const CLEANUP_VERSION = 1;
	const CLEANUP_OPTION  = 'emwa_role_cleanup_version';
	const LEGACY_CAPS     = array( 'edit_theme_options', 'edit_themes', 'update_themes', 'delete_themes', 'install_themes', 'upload_themes' );
	private static $cleanup_error;

	/** WordPress core does not grant this capability to these roles by default. */
	public static function has_core_default( $role ) {
		return in_array( $role, array( 'editor', 'author', 'contributor', 'subscriber' ), true );
	}

	public static function revision( $role ) {
		return hash( 'sha256', maybe_serialize( $role->capabilities ) );
	}

	/** Preserve the complete role before removing a stored capability. */
	private static function backup( $role, $capabilities ) {
		$previous = get_option( self::HISTORY, null );

		if ( null !== $previous && ! is_array( $previous ) ) {
			return new \WP_Error( 'emwa_role_backup', __( 'Role permission backup storage is invalid. No permissions were changed.', 'editor-menu-and-widget-access' ) );
		}

		$history = null === $previous ? array() : $previous;
		array_unshift(
			$history,
			array(
				'role'         => $role,
				'created'      => time(),
				'actor'        => get_current_user_id(),
				'capabilities' => $capabilities,
			)
		);
		$history = array_slice( $history, 0, 10 );
		$saved   = Settings::compare_option( self::HISTORY, $previous, $history );

		if ( ! $saved ) {
			return new \WP_Error( 'emwa_role_backup', __( 'The role permission backup could not be saved. No permissions were changed.', 'editor-menu-and-widget-access' ) );
		}

		return true;
	}

	/** Reset the requested legacy capability surface without losing concurrent role changes. */
	public static function cleanup( $apply = true ) {
		$backup = get_option( Migrations::BACKUP, null );

		if ( ! Settings::writable() || ! is_array( $backup ) || ! isset( $backup['options'], $backup['capabilities'] ) || ! is_array( $backup['options'] ) || ! is_array( $backup['capabilities'] ) ) {
			return new \WP_Error( 'emwa_initialization', __( 'Editor Access settings could not be initialized safely. Existing settings and role permissions were preserved.', 'editor-menu-and-widget-access' ) );
		}

		$option = wp_roles()->role_key;
		$before = get_option( $option, array() );

		if ( ! is_array( $before ) ) {
			return new \WP_Error( 'emwa_role_cleanup', __( 'Role permissions changed or could not be saved. No concurrent changes were overwritten. Reload and retry; a backup is available.', 'editor-menu-and-widget-access' ) );
		}

		$after = $before;

		foreach ( array( 'editor', 'author', 'contributor', 'subscriber' ) as $name ) {
			if ( ! isset( $after[ $name ]['capabilities'] ) || ! is_array( $after[ $name ]['capabilities'] ) ) {
				continue;
			}

			foreach ( self::LEGACY_CAPS as $cap ) {
				if ( ! empty( $after[ $name ]['capabilities'][ $cap ] ) ) {
					unset( $after[ $name ]['capabilities'][ $cap ] );
				}
			}
		}

		$legacy = Migrations::legacy_options();

		if ( isset( $after['administrator']['capabilities'], $legacy['emwa_roles'] ) && is_array( $after['administrator']['capabilities'] ) && is_array( $legacy['emwa_roles'] ) && array_key_exists( 'administrator', $legacy['emwa_roles'] ) ) {
			foreach ( array( 'edit_themes', 'update_themes', 'delete_themes', 'install_themes' ) as $cap ) {
				if ( ! array_key_exists( $cap, $after['administrator']['capabilities'] ) ) {
					$after['administrator']['capabilities'][ $cap ] = true;
				}
			}
		}

		foreach ( $after as $name => $details ) {
			if ( $details !== $before[ $name ] ) {
				$result = self::backup( $name, $before[ $name ]['capabilities'] );

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		if ( ! $apply ) {
			return true;
		}

		if ( $after !== $before && ! Settings::compare_option( $option, $before, $after ) ) {
			return new \WP_Error( 'emwa_role_cleanup', __( 'Role permissions changed or could not be saved. No concurrent changes were overwritten. Reload and retry; a backup is available.', 'editor-menu-and-widget-access' ) );
		}

		wp_roles()->for_site();
		wp_get_current_user()->get_role_caps();
		delete_option( Migrations::LEGACY_DEACTIVATION );
		update_option( self::CLEANUP_OPTION, self::CLEANUP_VERSION, false );

		return true;
	}

	/** Updates and new network sites also need cleanup; ordinary requests do not repeat it. */
	public static function maybe_cleanup() {
		if ( is_wp_error( Migrations::error() ) ) {
			self::$cleanup_error = Migrations::error();

			return;
		}

		if ( (int) get_option( self::CLEANUP_OPTION, 0 ) < self::CLEANUP_VERSION ) {
			$result = self::cleanup();

			if ( is_wp_error( $result ) ) {
				self::$cleanup_error = $result;
			}
		}
	}

	public static function notice() {
		if ( self::$cleanup_error && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( self::$cleanup_error->get_error_message() ) . '</p></div>';
		}
	}

	private static function activate_site( $apply ) {
		$result = Migrations::run();

		return is_wp_error( $result ) ? $result : self::cleanup( $apply );
	}

	/** Preflight all network backups before changing any site's role capabilities. */
	public static function activate( $network_wide = false ) {
		if ( $network_wide && is_multisite() ) {
			foreach ( array( false, true ) as $apply ) {
				$offset = 0;

				do {
					$sites = get_sites(
						array(
							'fields'     => 'ids',
							'network_id' => get_current_network_id(),
							'number'     => 100,
							'offset'     => $offset,
						)
					);

					foreach ( $sites as $site_id ) {
						switch_to_blog( $site_id );

						try {
							$result = self::activate_site( $apply );
						} finally {
							restore_current_blog();
						}

						if ( is_wp_error( $result ) ) {
							wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
						}
					}

					$offset    += 100;
					$site_count = count( $sites );
				} while ( 100 === $site_count );
			}

			return;
		}

		$result = self::activate_site( true );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
		}
	}

	/** Disable this plugin's grant; restore only WordPress-owned role defaults. */
	public static function reset( $role, $settings_revision, $role_revision ) {
		if ( ! is_string( $role ) || ! Routes::valid_string( $role, 191 ) || 'administrator' === $role ) {
			return new \WP_Error( 'emwa_role', __( 'Select an available non-administrator role.', 'editor-menu-and-widget-access' ) );
		}

		$stored_role = get_role( $role );

		if ( ! $stored_role || ! empty( $stored_role->capabilities['manage_options'] ) ) {
			return new \WP_Error( 'emwa_role', __( 'This role is unavailable or has administrator permissions.', 'editor-menu-and-widget-access' ) );
		}

		$current = Settings::get();

		if ( ! Settings::writable() || ! is_string( $settings_revision ) || ! hash_equals( Settings::revision( $current ), $settings_revision ) || ! is_string( $role_revision ) || ! hash_equals( self::revision( $stored_role ), $role_revision ) ) {
			return new \WP_Error( 'emwa_stale', __( 'Settings or role permissions changed since this page opened. Reload before resetting; nothing was changed.', 'editor-menu-and-widget-access' ) );
		}

		$role_option  = wp_roles()->role_key;
		$roles_before = get_option( $role_option, array() );

		if ( ! isset( $roles_before[ $role ]['capabilities'] ) || $roles_before[ $role ]['capabilities'] !== $stored_role->capabilities ) {
			return new \WP_Error( 'emwa_stale', __( 'Role permissions changed or could not be saved. No concurrent changes were overwritten. Reload and retry; a backup is available.', 'editor-menu-and-widget-access' ) );
		}

		$remove_stored = self::has_core_default( $role ) && ! empty( $stored_role->capabilities['edit_theme_options'] );

		if ( $remove_stored ) {
			$backup = self::backup( $role, $stored_role->capabilities );

			if ( is_wp_error( $backup ) ) {
				return $backup;
			}
		}

		$next = $current;

		if ( ! isset( $next['profiles'][ $role ] ) ) {
			$next['profiles'][ $role ] = Settings::profile( $role );
		}

		$next['profiles'][ $role ]['grant_appearance']    = false;
		$next['profiles'][ $role ]['restrict_customizer'] = false;
		$saved = Settings::save( $next, $settings_revision );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		if ( $remove_stored ) {
			$roles_after = $roles_before;

			unset( $roles_after[ $role ]['capabilities']['edit_theme_options'] );

			if ( ! Settings::compare_option( $role_option, $roles_before, $roles_after ) ) {
				return new \WP_Error( 'emwa_role', __( 'The plugin grant was disabled, but the stored role permission could not be removed. A backup is available; retry after checking role-management plugins.', 'editor-menu-and-widget-access' ) );
			}

			wp_roles()->for_site();
			wp_get_current_user()->get_role_caps();
		}

		if ( 'editor' === $role ) {
			// Retire the marker used by early version 4 development builds.
			delete_option( Migrations::LEGACY_DEACTIVATION );
		}

		return true;
	}
}
