<?php
/** Read-only snapshot of currently registered admin navigation. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess\Admin;

use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Routes;
defined( 'ABSPATH' ) || exit;

final class AccessPreview {
	/** Estimate a single role without borrowing an account's other roles or personal grants. */
	public static function menu_rules( $role, $profile, $items ) {
		$stored = get_role( $role );
		$caps   = $stored ? $stored->capabilities : array();
		$active = $stored && 'administrator' !== $role && empty( $caps['manage_options'] ) && in_array( $role, Settings::get()['roles'], true );

		if ( $active && $profile['grant_appearance'] && ! array_key_exists( 'edit_theme_options', $caps ) ) {
			$caps['edit_theme_options'] = true;
		}

		$allowed = static function ( $capability ) use ( $stored, $caps, $active, $profile ) {
			if ( ! $stored || ! is_string( $capability ) || '' === $capability ) {
				return null;
			}
			if ( 'customize' === $capability && $active && $profile['restrict_customizer'] ) {
				return false;
			}
			// Object-specific checks cannot be decided from a menu's capability alone.
			if ( in_array( $capability, array( 'edit_post', 'read_post', 'delete_post', 'edit_user', 'delete_user', 'promote_user', 'remove_user', 'edit_comment', 'edit_term', 'delete_term', 'assign_term', 'edit_post_meta', 'delete_post_meta', 'add_post_meta' ), true ) ) {
				return null;
			}
			// Zero means the viewing user to is_super_admin(); use a non-user ID instead.
			// Third-party mappers can require a real account or an object. An estimate must not break admin.
			set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Isolate third-party warnings during a non-user capability estimate.
				static function () {
					throw new \ErrorException( 'Capability preview unavailable.' );
				},
				E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
			);
			try {
				$required_caps = map_meta_cap( $capability, -1 );
				if ( ! is_array( $required_caps ) ) {
					return null;
				}
			} catch ( \Throwable $error ) {
				return null;
			} finally {
				restore_error_handler();
			}
			foreach ( $required_caps as $required ) {
				if ( ! is_string( $required ) ) {
					return null;
				}
				if ( 'do_not_allow' === $required || empty( $caps[ $required ] ) ) {
					return false;
				}
			}
			return true;
		};

		$parents = array();

		foreach ( $items as $id => &$item ) {
			$capability = $item['capability'] ?? '';
			// Core selects this capability using the viewing user's context.
			if ( '' === $item['parent'] && 'themes.php' === $item['slug'] && ! $allowed( 'switch_themes' ) ) {
				$capability = 'edit_theme_options';
			}
			$item['role_allowed'] = $allowed( $capability );
			if ( '' === $item['parent'] ) {
				$parents[ Routes::normalize( $item['slug'] ) ] = $id;
			}
		}

		unset( $item );

		foreach ( $items as &$item ) {
			$parent_id = $parents[ Routes::normalize( $item['parent'] ) ] ?? null;
			$parent    = null !== $parent_id ? $items[ $parent_id ] : null;
			$hidden    = RolePolicy::profile_hides_menu( $profile, $item['parent'], $item['slug'] );
			$reasons   = array();
			if ( empty( $item['available'] ) ) {
				$reasons[] = 'unavailable';
			} elseif ( false === $item['role_allowed'] ) {
				$reasons[] = 'permission';
			} elseif ( $parent && false === $parent['role_allowed'] ) {
				$reasons[] = 'parent_permission';
			} elseif ( null === $item['role_allowed'] ) {
				$reasons[] = 'unknown';
			}
			if ( $hidden ) {
				$reasons[] = $active ? 'rule' : 'inactive_rule';
			}
			if ( $active && $parent && RolePolicy::profile_hides_menu( $profile, '', $parent['slug'] ) ) {
				$reasons[] = 'parent_rule';
			}
			$item['role_available'] = ! empty( $item['available'] ) && false !== $item['role_allowed'] && ( ! $parent || false !== $parent['role_allowed'] );
			$item['role_status']    = $reasons ? $reasons : array( 'available' );
		}

		unset( $item );

		return $items;
	}

	public static function for_role( $role ) {
		if ( ! current_user_can( 'list_users' ) ) {
			return array(
				'user'       => false,
				'menus'      => array(),
				'appearance' => null,
			);
		}

		$ids = get_users(
			array(
				'role'   => $role,
				'number' => 1,
				'fields' => 'ids',
			)
		);

		$user = $ids ? get_userdata( $ids[0] ) : false;
		if ( ! $user ) {
			return array(
				'user'       => false,
				'menus'      => array(),
				'appearance' => null,
			);
		}

		$menus = array();

		foreach ( Catalog::menus() as $item ) {
			if ( 0 !== $item['depth'] ) {
				continue;
			}
			$capability = $item['capability'];
			$menus[]    = array(
				'label'   => $item['label'],
				'hidden'  => RolePolicy::hides_menu( $user, $item['parent'], $item['slug'] ),
				'allowed' => is_string( $capability ) && '' !== $capability ? user_can( $user, $capability ) : null,
			);
		}

		return array(
			'user'       => $user,
			'menus'      => $menus,
			'appearance' => user_can( $user, 'edit_theme_options' ),
		);
	}
}
