<?php
/** Authenticated settings submissions and downloads. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess\Admin;

use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\ProfileCopy;

defined( 'ABSPATH' ) || exit;

final class SettingsActions {
	private static function authorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Editor Access.', 'editor-menu-and-widget-access' ), '', array( 'response' => 403 ) );
		}
	}

	public static function save() {
		self::authorize();
		check_admin_referer( 'emwa_save' );

		$input   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$tab     = isset( $input['tab'] ) && is_string( $input['tab'] ) ? $input['tab'] : 'roles';
		$role    = isset( $input['role'] ) && is_string( $input['role'] ) ? $input['role'] : '';
		$current = Settings::get();
		$result  = new \WP_Error( 'emwa_form', __( 'The form was truncated or incomplete. Reload before saving. Existing settings were preserved.', 'editor-menu-and-widget-access' ) );

		if ( 'yes' === ( $input['complete'] ?? '' ) ) {
			if ( 'import' === ( $input['intent'] ?? '' ) ) {
				$next = Settings::import( $input['import_json'] ?? null, $current );
			} elseif ( 'restore' === ( $input['intent'] ?? '' ) ) {
				$history = get_option( Settings::HISTORY, array() );
				$key     = $input['backup'] ?? '';
				$next    = new \WP_Error( 'emwa_backup', __( 'The selected backup is unavailable.', 'editor-menu-and-widget-access' ) );

				foreach ( is_array( $history ) ? $history : array() as $entry ) {
					if ( isset( $entry['settings'] ) && is_string( $key ) && hash_equals( Settings::revision( $entry['settings'] ), $key ) ) {
						$next = $entry['settings'];
						break;
					}
				}
			} else {
				$items = SettingsForm::decode_manifest( $input['manifest'] ?? null, $input['signature'] ?? null, $tab, $role );
				$next  = is_wp_error( $items ) ? $items : SettingsForm::patch( $current, $input, $items );
			}

			$result = is_wp_error( $next ) ? $next : Settings::save( $next, $input['revision'] ?? null );
		}

		// Store a per-user notice rather than putting arbitrary text in a redirect URL.
		set_transient(
			'emwa_notice_' . get_current_user_id(),
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Settings saved. Previous settings are available in Help.', 'editor-menu-and-widget-access' ),
			),
			60
		);

		wp_safe_redirect( SettingsPage::url( in_array( $tab, SettingsPage::TABS, true ) ? $tab : 'roles', $role ) );
		exit;
	}

	public static function reset_role_permissions() {
		self::authorize();

		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit role capabilities.', 'editor-menu-and-widget-access' ), '', array( 'response' => 403 ) );
		}

		$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked below.
		$role  = isset( $input['role'] ) && is_string( $input['role'] ) ? sanitize_text_field( $input['role'] ) : '';
		check_admin_referer( 'emwa_reset_role_permissions_' . $role );

		$before_role     = get_role( $role );
		$before_settings = Settings::get();
		$before          = array(
			'stored' => $before_role && ! empty( $before_role->capabilities['edit_theme_options'] ),
			'plugin' => ! empty( $before_settings['profiles'][ $role ]['grant_appearance'] ) && in_array( $role, $before_settings['roles'], true ),
		);
		$result          = 'yes' === ( $input['complete'] ?? '' ) ? RolePermissions::reset( $role, $input['revision'] ?? null, $input['role_revision'] ?? null ) : new \WP_Error( 'emwa_form', __( 'The permission-reset form was incomplete. Nothing was changed.', 'editor-menu-and-widget-access' ) );
		$message         = RolePermissions::has_core_default( $role ) ? __( 'This role’s Appearance permission was reset to the WordPress default. This plugin’s grant and Customizer restriction are off for the role.', 'editor-menu-and-widget-access' ) : __( 'This plugin’s grant and Customizer restriction are off for the role. Stored permissions owned by WooCommerce or other plugins were preserved.', 'editor-menu-and-widget-access' );
		$after_role      = get_role( $role );
		$after_settings  = Settings::get();

		set_transient(
			'emwa_notice_' . get_current_user_id(),
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? $result->get_error_message() : $message,
				'details' => is_wp_error( $result ) ? null : array(
					'before' => $before,
					'after'  => array(
						'stored' => $after_role && ! empty( $after_role->capabilities['edit_theme_options'] ),
						'plugin' => ! empty( $after_settings['profiles'][ $role ]['grant_appearance'] ) && in_array( $role, $after_settings['roles'], true ),
					),
				),
			),
			60
		);

		wp_safe_redirect( SettingsPage::url( 'roles', $role ) );
		exit;
	}

	public static function copy_profile() {
		self::authorize();
		$input  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked below.
		$source = isset( $input['source'] ) && is_string( $input['source'] ) ? sanitize_text_field( $input['source'] ) : '';
		$target = isset( $input['target'] ) && is_string( $input['target'] ) ? sanitize_text_field( $input['target'] ) : '';
		check_admin_referer( 'emwa_copy_profile_' . $source . '_' . $target );

		$result = 'yes' === ( $input['complete'] ?? '' ) ? ProfileCopy::copy( $source, $target, $input['revision'] ?? null ) : new \WP_Error( 'emwa_form', __( 'The profile-copy form was incomplete. Nothing was changed.', 'editor-menu-and-widget-access' ) );

		set_transient(
			'emwa_notice_' . get_current_user_id(),
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Profile copied. The target role’s previous settings are available in Help & Backups.', 'editor-menu-and-widget-access' ),
			),
			60
		);

		wp_safe_redirect( SettingsPage::url( 'roles', $target ) );
		exit;
	}

	public static function export() {
		self::authorize();
		check_admin_referer( 'emwa_export' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="editor-access-settings.json"' );
		echo wp_json_encode( Settings::export( get_option( Settings::OPTION, Settings::get() ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	public static function export_role_permissions() {
		self::authorize();

		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( esc_html__( 'You do not have permission to view role capability backups.', 'editor-menu-and-widget-access' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'emwa_export_role_permissions' );
		$history = get_option( RolePermissions::HISTORY, array() );

		if ( ! is_array( $history ) ) {
			wp_die( esc_html__( 'Role capability backup data is invalid.', 'editor-menu-and-widget-access' ), '', array( 'response' => 500 ) );
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="editor-access-role-permissions.json"' );
		echo wp_json_encode(
			array(
				'plugin'  => 'editor-menu-and-widget-access',
				'type'    => 'role_permission_backups',
				'entries' => $history,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}
}
