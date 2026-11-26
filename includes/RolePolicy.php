<?php
/** Shared role matching and visibility precedence. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class RolePolicy {
	public static function exempt( $user ) {
		return ! $user instanceof \WP_User || ! $user->ID || in_array( 'administrator', (array) $user->roles, true ) || ! empty( $user->allcaps['manage_options'] ) || ( is_multisite() && is_super_admin( $user->ID ) );
	}

	public static function profiles( $user ) {
		if ( self::exempt( $user ) ) {
			return array();
		}

		$config   = Settings::get();
		$profiles = array();

		foreach ( array_intersect( (array) $user->roles, $config['roles'] ) as $role ) {
			if ( get_role( $role ) && isset( $config['profiles'][ $role ] ) ) {
				$profiles[ $role ] = $config['profiles'][ $role ];
			}
		}

		return $profiles;
	}

	public static function grants_appearance( $user ) {
		foreach ( self::profiles( $user ) as $profile ) {
			if ( $profile['grant_appearance'] ) {
				return true;
			}
		}

		return false;
	}

	/** Untouched fresh defaults follow theme changes; explicit saves remain authoritative. */
	public static function appearance_hidden( $profile, $area ) {
		if ( 'site_editor' === $area && ! empty( $profile['auto_site_editor'] ) ) {
			return ! wp_is_block_theme();
		}

		return ! empty( $profile['hidden_appearance'][ $area ] );
	}

	/** Exact rules override legacy wildcard rules within each role. */
	public static function profile_hides_menu( $profile, $parent_slug, $slug ) {
		$id       = Routes::id( $parent_slug, $slug );
		$wildcard = Routes::id( '*', $slug );

		if ( isset( $profile['menus'][ $id ] ) ) {
			return $profile['menus'][ $id ]['hidden'];
		}

		if ( isset( $profile['menus'][ $wildcard ] ) ) {
			return $profile['menus'][ $wildcard ]['hidden'];
		}

		$area = Routes::appearance_area( $parent_slug, $slug );

		return $area && self::appearance_hidden( $profile, $area );
	}

	/** Hiding wins across selected roles, independent of role order. */
	public static function hides_menu( $user, $parent_slug, $slug ) {
		foreach ( self::profiles( $user ) as $profile ) {
			if ( self::profile_hides_menu( $profile, $parent_slug, $slug ) ) {
				return true;
			}
		}

		return false;
	}

	public static function hides_area( $user, $area ) {
		foreach ( self::profiles( $user ) as $profile ) {
			if ( self::appearance_hidden( $profile, $area ) ) {
				return true;
			}
		}

		return false;
	}
}
