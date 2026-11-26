<?php
/** Form validation and partial updates for Editor Access settings. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess\Admin;

use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Routes;

defined( 'ABSPATH' ) || exit;

final class SettingsForm {
	private static function valid_choices( $values, $allowed ) {
		if ( ! is_array( $values ) ) {
			return false;
		}

		$allowed = array_map( 'strval', $allowed );

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
				return false;
			}
		}

		return true;
	}

	public static function manifest( $tab, $role, $items ) {
		$data = wp_json_encode(
			array(
				'tab'   => $tab,
				'role'  => $role,
				'items' => $items,
			),
			JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG
		);

		return array( $data, hash_hmac( 'sha256', $data, wp_salt( 'nonce' ) ) );
	}

	public static function decode_manifest( $data, $signature, $tab, $role ) {
		if ( ! is_string( $data ) || ! is_string( $signature ) || strlen( $data ) > 524288 || ! hash_equals( hash_hmac( 'sha256', $data, wp_salt( 'nonce' ) ), $signature ) ) {
			return new \WP_Error( 'emwa_form', __( 'The form is incomplete or invalid. Reload the page; settings were not changed.', 'editor-menu-and-widget-access' ) );
		}

		$manifest = json_decode( $data, true );

		if ( ! is_array( $manifest ) || ( $manifest['tab'] ?? null ) !== $tab || ( $manifest['role'] ?? null ) !== $role || ! isset( $manifest['items'] ) || ! is_array( $manifest['items'] ) ) {
			return new \WP_Error( 'emwa_form', __( 'Invalid form details.', 'editor-menu-and-widget-access' ) );
		}

		return $manifest['items'];
	}

	/** Patch only fields shown in the signed form. Keep absent plugins and other tabs intact. */
	public static function patch( $current, $input, $items ) {
		$tab  = $input['tab'] ?? '';
		$role = $input['role'] ?? '';

		if ( ! in_array( $tab, SettingsPage::TABS, true ) || ! Routes::valid_string( $role, 191 ) || ( ! isset( $current['profiles'][ $role ] ) && ( 'roles' !== $tab || ! isset( $items[ $role ] ) ) ) ) {
			return new \WP_Error( 'emwa_form', __( 'Select an existing role profile before saving.', 'editor-menu-and-widget-access' ) );
		}

		$next    = $current;
		$profile = $current['profiles'][ $role ] ?? Settings::profile( $role );
		$reset   = 'reset' === ( $input['intent'] ?? '' );

		if ( 'roles' === $tab ) {
			$selected = $input['selected_roles'] ?? array();

			if ( ! self::valid_choices( $selected, array_keys( $items ) ) ) {
				return new \WP_Error( 'emwa_role', __( 'Invalid role selection.', 'editor-menu-and-widget-access' ) );
			}

			$next['roles'] = array_values( array_unique( $selected ) );

			foreach ( $selected as $selected_role ) {
				if ( ! isset( $next['profiles'][ $selected_role ] ) ) {
					$next['profiles'][ $selected_role ] = Settings::profile( $selected_role );
				}
			}

			if ( $reset ) {
				$defaults                       = Settings::profile( $role );
				$profile['grant_appearance']    = $defaults['grant_appearance'];
				$profile['restrict_customizer'] = false;
				$next['roles']                  = $current['roles'];

				// Forms opened before the UI update still own Appearance visibility.
				if ( ! isset( $input['emwa_roles_ui'] ) ) {
					unset( $profile['auto_site_editor'] );
					$profile['hidden_appearance'] = $defaults['hidden_appearance'];
				}
			} else {
				$profile['grant_appearance']    = isset( $input['grant_appearance'] );
				$profile['restrict_customizer'] = isset( $input['restrict_customizer'] );

				if ( ! isset( $input['emwa_roles_ui'] ) ) {
					$areas = $input['hidden_appearance'] ?? array();

					if ( ! self::valid_choices( $areas, Settings::AREAS ) ) {
						return new \WP_Error( 'emwa_area', __( 'Invalid appearance selection.', 'editor-menu-and-widget-access' ) );
					}

					unset( $profile['auto_site_editor'] );
					$profile['hidden_appearance'] = array_fill_keys( $areas, true );
				}
			}
		} elseif ( in_array( $tab, array( 'menus', 'toolbar' ), true ) ) {
			$hidden = $input['hidden'] ?? array();

			if ( ! self::valid_choices( $hidden, array_keys( $items ) ) ) {
				return new \WP_Error( 'emwa_rule', __( 'Invalid visibility selection.', 'editor-menu-and-widget-access' ) );
			}

			if ( $reset ) {
				$profile[ $tab ] = array();

				if ( 'toolbar' === $tab ) {
					$profile['toolbar_scope'] = 'both';
				} elseif ( isset( $input['emwa_appearance_ui'] ) ) {
					unset( $profile['auto_site_editor'] );
					$profile['hidden_appearance'] = Settings::profile( $role )['hidden_appearance'];
				}
			} else {
				if ( 'menus' === $tab && isset( $input['emwa_appearance_ui'] ) ) {
					$areas = $input['hidden_appearance'] ?? array();

					if ( ! self::valid_choices( $areas, Settings::AREAS ) ) {
						return new \WP_Error( 'emwa_area', __( 'Invalid appearance selection.', 'editor-menu-and-widget-access' ) );
					}

					unset( $profile['auto_site_editor'] );
					$profile['hidden_appearance'] = array_fill_keys( $areas, true );
				}

				foreach ( $items as $id => $item ) {
					$is_hidden = in_array( (string) $id, $hidden, true );

					if ( 'menus' === $tab ) {
						// Core appearance rows are configured by the controls above the menu catalog.
						if ( ! empty( $item['managed'] ) ) {
							continue;
						}

						$profile['menus'][ $id ] = array(
							'parent' => $item['parent'],
							'slug'   => $item['slug'],
							'hidden' => $is_hidden,
						);
					} else {
						$profile['toolbar'][ $id ] = $is_hidden;
					}
				}

				if ( 'toolbar' === $tab ) {
					$scope = $input['toolbar_scope'] ?? '';

					if ( ! in_array( $scope, array( 'admin', 'frontend', 'both' ), true ) ) {
						return new \WP_Error( 'emwa_scope', __( 'Invalid toolbar scope.', 'editor-menu-and-widget-access' ) );
					}

					$profile['toolbar_scope'] = $scope;
				}
			}
		} else {
			return new \WP_Error( 'emwa_form', __( 'Invalid settings action.', 'editor-menu-and-widget-access' ) );
		}

		$next['profiles'][ $role ] = $profile;

		return $next;
	}
}
