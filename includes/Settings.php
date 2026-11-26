<?php
/** Versioned settings and revision-checked persistence. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class Settings {
	private static $cached;
	const OPTION  = 'emwa_config';
	const HISTORY = 'emwa_settings_backups';
	const SCHEMA  = 1;
	const AREAS   = array( 'appearance', 'themes', 'customize', 'widgets', 'menus', 'site_editor', 'identity', 'styles', 'patterns', 'templates', 'fonts' );

	/** Custom roles do not receive a broad grant just because they were selected. */
	public static function profile( $role = '' ) {
		return array(
			'grant_appearance'    => in_array( $role, array( 'editor', 'shop_manager' ), true ),
			'hidden_appearance'   => array( 'themes' => true ),
			'menus'               => array(),
			'toolbar'             => array(),
			'toolbar_scope'       => 'both',
			'restrict_customizer' => false,
		);
	}

	/** First-install visibility defaults; never applied over saved or legacy profiles. */
	public static function defaults() {
		$config = self::from_legacy( array() );

		foreach ( $config['profiles'] as &$profile ) {
			$profile['auto_site_editor']  = true;
			$profile['hidden_appearance'] = array_fill_keys( array( 'themes', 'customize', 'identity', 'styles', 'patterns', 'templates', 'fonts' ), true );

			// Block-theme Navigation needs the Site Editor entry point.
			if ( ! wp_is_block_theme() ) {
				$profile['hidden_appearance']['site_editor'] = true;
			}
		}

		unset( $profile );

		return $config;
	}

	public static function from_legacy( $legacy ) {
		$roles = array( 'editor', 'shop_manager' );

		// Version 3 treated an empty saved role list as Editor/Shop Manager defaults.
		if ( ! empty( $legacy['emwa_roles'] ) && is_array( $legacy['emwa_roles'] ) ) {
			$roles = array_filter(
				array_map( 'strval', array_keys( $legacy['emwa_roles'] ) ),
				static function ( $role ) {
					return Routes::valid_string( $role, 191 );
				}
			);
		}

		$config  = array(
			'schema_version' => self::SCHEMA,
			'roles'          => array_values( $roles ),
			'profiles'       => array(),
		);
		$old     = isset( $legacy['emwa_settings'] ) && is_array( $legacy['emwa_settings'] ) ? $legacy['emwa_settings'] : array();
		$toolbar = isset( $legacy['emwaAdminbar'] ) && is_array( $legacy['emwaAdminbar'] ) ? $legacy['emwaAdminbar'] : array();

		foreach ( $roles as $role ) {
			$profile = self::profile( $role );

			foreach ( array(
				'emwa_chk_custom'  => 'customize',
				'emwa_chk_widgets' => 'widgets',
				'emwa_chk_menus'   => 'menus',
			) as $key => $area ) {
				// Legacy code treated any non-null value as checked, even zero.
				if ( isset( $old[ $key ] ) ) {
					$profile['hidden_appearance'][ $area ] = true;
				}
			}

			if ( isset( $old['emwa_chk_custom'] ) ) {
				// The old slot also hid Site Editor on modern core. Retain that visibility.
				$profile['hidden_appearance']['site_editor'] = true;
				$profile['hidden_appearance']['patterns']    = true;
			}

			if ( isset( $old['emwa_chk_custom'], $old['emwa_chk_widgets'], $old['emwa_chk_menus'] ) ) {
				$profile['hidden_appearance']['appearance'] = true;
			}

			foreach ( $old as $slug => $value ) {
				$slug = (string) $slug;

				if ( null !== $value && Routes::valid_string( $slug ) && 0 !== strpos( $slug, 'emwa_chk_' ) ) {
					$profile['menus'][ Routes::id( '*', $slug ) ] = array(
						'parent' => '*',
						'slug'   => $slug,
						'hidden' => true,
					);
				}
			}

			if ( isset( $old['vc-general'] ) ) {
				$profile['menus'][ Routes::id( '*', 'vc-welcome' ) ] = array(
					'parent' => '*',
					'slug'   => 'vc-welcome',
					'hidden' => true,
				);
			}

			foreach ( $toolbar as $id => $value ) {
				$id = (string) $id;

				if ( null !== $value && Routes::valid_string( $id, 512 ) ) {
					$profile['toolbar'][ $id ] = true;
				}
			}

			$config['profiles'][ $role ] = $profile;
		}

		return $config;
	}

	/** Malformed or future data stays untouched and grants no new permissions. */
	public static function invalidate( $option = null ) {
		if ( null === $option || ( is_string( $option ) && 0 === strpos( $option, 'emwa' ) ) ) {
			self::$cached = null;
		}
	}

	public static function get() {
		if ( null !== self::$cached ) {
			return self::$cached;
		}

		$config = get_option( self::OPTION, null );

		if ( null !== $config && ! is_wp_error( self::validate( $config ) ) ) {
			self::$cached = $config;
		} else {
			// Never grant new access before settings and their migration backup are durable.
			self::$cached = array(
				'schema_version' => self::SCHEMA,
				'roles'          => array(),
				'profiles'       => array(),
			);
		}

		return self::$cached;
	}

	public static function writable() {
		return ! is_wp_error( self::validate( get_option( self::OPTION, null ) ) );
	}

	/** Validate storage/imports; keep unknown fields for forward-compatible partial saves. */
	public static function validate( $config ) {
		if ( ! is_array( $config ) || ( $config['schema_version'] ?? null ) !== self::SCHEMA || ! isset( $config['roles'], $config['profiles'] ) || ! is_array( $config['roles'] ) || ! is_array( $config['profiles'] ) ) {
			return new \WP_Error( 'emwa_schema', __( 'Settings are malformed or use an unsupported version. Existing data has been preserved.', 'editor-menu-and-widget-access' ) );
		}

		if ( count( $config['roles'] ) > 200 || count( $config['profiles'] ) > 200 || strlen( maybe_serialize( $config ) ) > 1048576 ) {
			return new \WP_Error( 'emwa_size', __( 'Settings exceed the supported size.', 'editor-menu-and-widget-access' ) );
		}

		foreach ( $config['roles'] as $role ) {
			if ( ! Routes::valid_string( $role, 191 ) || ! isset( $config['profiles'][ $role ] ) ) {
				return new \WP_Error( 'emwa_role', __( 'Every selected role must have a valid profile.', 'editor-menu-and-widget-access' ) );
			}
		}

		foreach ( $config['profiles'] as $role => $profile ) {
			$role = (string) $role;

			if ( ! Routes::valid_string( $role, 191 ) || ! is_array( $profile ) || ! isset( $profile['grant_appearance'], $profile['restrict_customizer'], $profile['hidden_appearance'], $profile['menus'], $profile['toolbar'], $profile['toolbar_scope'] ) || ! is_bool( $profile['grant_appearance'] ) || ! is_bool( $profile['restrict_customizer'] ) || ! is_array( $profile['hidden_appearance'] ) || ! is_array( $profile['menus'] ) || ! is_array( $profile['toolbar'] ) || ! in_array( $profile['toolbar_scope'], array( 'admin', 'frontend', 'both' ), true ) ) {
				return new \WP_Error( 'emwa_profile', __( 'Invalid role profile.', 'editor-menu-and-widget-access' ) );
			}

			if ( isset( $profile['auto_site_editor'] ) && ! is_bool( $profile['auto_site_editor'] ) ) {
				return new \WP_Error( 'emwa_profile', __( 'Invalid role profile.', 'editor-menu-and-widget-access' ) );
			}

			foreach ( $profile['hidden_appearance'] as $area => $hidden ) {
				if ( ! in_array( $area, self::AREAS, true ) || ! is_bool( $hidden ) ) {
					return new \WP_Error( 'emwa_area', __( 'Invalid appearance rule.', 'editor-menu-and-widget-access' ) );
				}
			}

			if ( count( $profile['menus'] ) > 2000 || count( $profile['toolbar'] ) > 2000 ) {
				return new \WP_Error( 'emwa_rules', __( 'Too many visibility rules.', 'editor-menu-and-widget-access' ) );
			}

			foreach ( $profile['menus'] as $id => $rule ) {
				if ( ! is_array( $rule ) || ! isset( $rule['parent'], $rule['slug'], $rule['hidden'] ) || ! Routes::valid_string( $rule['parent'], 2048, true ) || ! Routes::valid_string( $rule['slug'] ) || ! is_bool( $rule['hidden'] ) || Routes::id( $rule['parent'], $rule['slug'] ) !== $id ) {
					return new \WP_Error( 'emwa_menu', __( 'Invalid menu rule.', 'editor-menu-and-widget-access' ) );
				}
			}

			foreach ( $profile['toolbar'] as $id => $hidden ) {
				if ( ! Routes::valid_string( (string) $id, 512 ) || ! is_bool( $hidden ) ) {
					return new \WP_Error( 'emwa_toolbar', __( 'Invalid toolbar rule.', 'editor-menu-and-widget-access' ) );
				}
			}
		}

		return true;
	}

	public static function revision( $config ) {
		return hash( 'sha256', maybe_serialize( $config ) );
	}

	/** Compare the whole option so another request's edits cannot be overwritten. */
	public static function compare_option( $option, $before, $after ) {
		global $wpdb;

		if ( null === $before ) {
			add_option( $option, $after, '', false );

			return get_option( $option, null ) === $after;
		}

		$filtered = sanitize_option( $option, $after );
		$filtered = apply_filters( "pre_update_option_{$option}", $filtered, $before, $option ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve core Options API filters and notifications.
		$filtered = apply_filters( 'pre_update_option', $filtered, $option, $before ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve core Options API filters and notifications.

		if ( $filtered !== $after ) {
			return false;
		}

		do_action( 'update_option', $option, $before, $after ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve core Options API filters and notifications.
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s", maybe_serialize( $after ), $option, maybe_serialize( $before ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic option comparison.
		wp_cache_delete( $option, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		if ( 1 !== $result ) {
			$actual = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Verify a concurrent identical write.

			return maybe_serialize( $after ) === $actual;
		}

		do_action( "update_option_{$option}", $before, $after, $option ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve core Options API filters and notifications.
		do_action( 'updated_option', $option, $before, $after ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve core Options API filters and notifications.

		return true;
	}

	/** Back up before each change; preserve ten recent complete configurations. */
	public static function backup( $current ) {
		$previous = get_option( self::HISTORY, null );

		if ( null !== $previous && ! is_array( $previous ) ) {
			return new \WP_Error( 'emwa_backup', __( 'The settings backup is unreadable. No settings were changed.', 'editor-menu-and-widget-access' ) );
		}

		$history   = null === $previous ? array() : $previous;
		$history[] = array(
			'time'     => time(),
			'settings' => $current,
		);
		$history   = array_slice( $history, -10 );

		if ( ! self::compare_option( self::HISTORY, $previous, $history ) ) {
			return new \WP_Error( 'emwa_backup', __( 'Could not save a backup. No settings were changed.', 'editor-menu-and-widget-access' ) );
		}

		return true;
	}

	/** Atomic comparison prevents two admins from silently overwriting each other. */
	public static function save( $next, $revision ) {
		global $wpdb;

		$valid = self::validate( $next );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$current = get_option( self::OPTION, null );

		if ( ! self::writable() || ! is_string( $revision ) || ! hash_equals( self::revision( $current ), $revision ) ) {
			return new \WP_Error( 'emwa_stale', __( 'Settings changed since this page was opened, or cannot be safely edited. Reload before trying again. No settings were changed.', 'editor-menu-and-widget-access' ) );
		}

		if ( $current === $next ) {
			return true;
		}

		$backup = self::backup( $current );

		if ( is_wp_error( $backup ) ) {
			return $backup;
		}

		// Direct SQL is intentional: the Options API has no atomic compare-and-swap.
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s", maybe_serialize( $next ), self::OPTION, maybe_serialize( $current ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( self::OPTION, 'options' );
		self::invalidate();
		wp_cache_delete( 'alloptions', 'options' );

		if ( 1 !== $result ) {
			return new \WP_Error( 'emwa_stale', __( 'Another save won the race or the database rejected this save. Reload the settings page. Your previous settings are preserved.', 'editor-menu-and-widget-access' ) );
		}

		do_action( 'update_option_' . self::OPTION, $current, $next, self::OPTION ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the core Options API notification.
		do_action( 'updated_option', self::OPTION, $current, $next ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the core Options API notification.

		return true;
	}

	/** Strict JSON envelope; imports merge profiles and retain other roles. */
	public static function import( $json, $current ) {
		if ( ! is_string( $json ) || strlen( $json ) > 1048576 ) {
			return new \WP_Error( 'emwa_import', __( 'Import must be JSON smaller than 1 MB.', 'editor-menu-and-widget-access' ) );
		}

		$data = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) || ( $data['plugin'] ?? '' ) !== 'editor-menu-and-widget-access' || ( $data['schema_version'] ?? null ) !== self::SCHEMA ) {
			return new \WP_Error( 'emwa_import', __( 'This is not a supported Editor Access settings export.', 'editor-menu-and-widget-access' ) );
		}

		$valid = self::validate( $data['settings'] ?? null );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$next             = $current;
		$next['roles']    = array_values( array_unique( array_merge( $current['roles'], $data['settings']['roles'] ) ) );
		$next['profiles'] = array_replace( $current['profiles'], $data['settings']['profiles'] );

		return $next;
	}

	public static function export( $config ) {
		return array(
			'plugin'         => 'editor-menu-and-widget-access',
			'schema_version' => self::SCHEMA,
			'settings'       => $config,
		);
	}
}
