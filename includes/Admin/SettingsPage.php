<?php
/** Native admin settings page and rendering. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess\Admin;

use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\Migrations;
use EditorMenuWidgetAccess\Routes;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {
	const SLUG = 'editorMenuWidgetAccess';
	const TABS = array( 'roles', 'menus', 'toolbar', 'help' );

	public static function register_hooks() {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_post_emwa_save', array( self::class, 'save' ) );
		add_action( 'admin_post_emwa_export', array( self::class, 'export' ) );
		add_action( 'admin_post_emwa_reset_role_permissions', array( self::class, 'reset_role_permissions' ) );
		add_action( 'admin_post_emwa_copy_profile', array( self::class, 'copy_profile' ) );
		add_action( 'admin_post_emwa_export_role_permissions', array( self::class, 'export_role_permissions' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( \EditorMenuWidgetAccess\PLUGIN_FULL_PATH ), array( self::class, 'links' ) );
	}

	public static function menu() {
		add_options_page( __( 'Editor Access', 'editor-menu-and-widget-access' ), __( 'Editor Access', 'editor-menu-and-widget-access' ), 'manage_options', self::SLUG, array( self::class, 'render' ) );

		// Keep historical bookmarks working without duplicating the visible menu.
		add_submenu_page( 'themes.php', __( 'Editor Access', 'editor-menu-and-widget-access' ), __( 'Editor Access', 'editor-menu-and-widget-access' ), 'manage_options', self::SLUG, array( self::class, 'render' ) );
		remove_submenu_page( 'themes.php', self::SLUG );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'editor-menu-and-widget-access' ) . '</a>' );

		return $links;
	}

	public static function url( $tab = 'roles', $role = '' ) {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'tab'  => $tab,
				'role' => $role,
			),
			admin_url( 'options-general.php' )
		);
	}

	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'settings_page_' . self::SLUG, 'appearance_page_' . self::SLUG ), true ) ) {
			return;
		}

		$style_version  = \EditorMenuWidgetAccess\VERSION . '.' . substr( md5_file( \EditorMenuWidgetAccess\PLUGIN_DIR . 'assets/admin/settings.css' ), 0, 12 );
		$script_version = \EditorMenuWidgetAccess\VERSION . '.' . substr( md5_file( \EditorMenuWidgetAccess\PLUGIN_DIR . 'assets/admin/settings.js' ), 0, 12 );
		wp_enqueue_style( 'emwa-settings', plugins_url( 'assets/admin/settings.css', \EditorMenuWidgetAccess\PLUGIN_FULL_PATH ), array(), $style_version );
		wp_enqueue_script( 'emwa-settings', plugins_url( 'assets/admin/settings.js', \EditorMenuWidgetAccess\PLUGIN_FULL_PATH ), array(), $script_version, true );
		wp_localize_script(
			'emwa-settings',
			'emwaSettings',
			array(
				'unsaved'            => __( 'You have unsaved changes.', 'editor-menu-and-widget-access' ),
				'confirmReset'       => __( 'Reset this role’s settings on the current tab? Selected roles and other tabs stay the same. A backup will be saved first.', 'editor-menu-and-widget-access' ),
				'confirmRestore'     => __( 'Restore this saved configuration? Your current settings will be backed up first.', 'editor-menu-and-widget-access' ),
				'confirmPermissions' => __( 'Reset Appearance permissions for this role? Built-in WordPress roles will lose any stored edit_theme_options grant. A backup is saved first.', 'editor-menu-and-widget-access' ),
				'confirmCopy'        => __( 'Copy the reviewed profile to this role? Its existing profile will be backed up first.', 'editor-menu-and-widget-access' ),
				'copied'             => __( 'Copied.', 'editor-menu-and-widget-access' ),
				'expand'             => __( 'Expand', 'editor-menu-and-widget-access' ),
				'collapse'           => __( 'Collapse', 'editor-menu-and-widget-access' ),
				/* translators: 1: localized number of displayed items, 2: localized total number of items. */
				'itemCount'          => __( '%1$s / %2$s', 'editor-menu-and-widget-access' ),
			)
		);
	}

	private static function authorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Editor Access.', 'editor-menu-and-widget-access' ), '', array( 'response' => 403 ) );
		}
	}

	public static function manifest( $tab, $role, $items ) {
		return SettingsForm::manifest( $tab, $role, $items );
	}

	public static function decode_manifest( $data, $signature, $tab, $role ) {
		return SettingsForm::decode_manifest( $data, $signature, $tab, $role );
	}

	public static function patch( $current, $input, $items ) {
		return SettingsForm::patch( $current, $input, $items );
	}

	public static function save() {
		return SettingsActions::save();
	}

	public static function reset_role_permissions() {
		return SettingsActions::reset_role_permissions();
	}

	public static function copy_profile() {
		return SettingsActions::copy_profile();
	}

	public static function export() {
		return SettingsActions::export();
	}

	public static function export_role_permissions() {
		return SettingsActions::export_role_permissions();
	}

	public static function render() {
		self::authorize();
		$config = Settings::get();
		$tab    = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'roles'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
		$tab    = array(
			'adminmenus' => 'menus',
			'adminbar'   => 'toolbar',
		)[ $tab ] ?? $tab;

		if ( ! in_array( $tab, self::TABS, true ) ) {
			$tab = 'roles';
		}

		$roles = wp_roles()->get_names();

		unset( $roles['administrator'] );

		foreach ( $config['profiles'] as $key => $unused ) {
			if ( 'administrator' !== $key && ! isset( $roles[ $key ] ) ) {
				$roles[ $key ] = $key;
			}
		}

		$role = isset( $_GET['role'] ) && is_string( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Whitelisted against registered roles below.

		if ( ! $roles ) {
			$role = '';
			$tab  = 'help';
		} elseif ( ! isset( $roles[ $role ] ) ) {
			$preferred = $config['roles'][0] ?? '';
			$role      = (string) ( isset( $roles[ $preferred ] ) ? $preferred : ( isset( $roles['editor'] ) ? 'editor' : array_key_first( $roles ) ) );
		}

		$profile     = $config['profiles'][ $role ] ?? Settings::profile( $role );
		$copy_target = isset( $_GET['copy_target'] ) && is_string( $_GET['copy_target'] ) ? sanitize_text_field( wp_unslash( $_GET['copy_target'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preview selection only.

		if ( $copy_target === $role || ! isset( $roles[ $copy_target ] ) || ! get_role( $copy_target ) || ! empty( get_role( $copy_target )->capabilities['manage_options'] ) ) {
			$copy_target = '';
		}

		$writable = Settings::writable();
		$notice   = get_transient( 'emwa_notice_' . get_current_user_id() );
		delete_transient( 'emwa_notice_' . get_current_user_id() );
		$items    = 'menus' === $tab ? Catalog::menu_rules( $profile ) : ( 'toolbar' === $tab ? Catalog::toolbar_rules( $profile ) : $roles );
		$show_all = isset( $_GET['show_all'] ) && is_string( $_GET['show_all'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['show_all'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.

		if ( 'menus' === $tab ) {
			foreach ( $items as &$item ) {
				$item['managed'] = '' !== Routes::appearance_area( $item['parent'], $item['slug'] );
			}

			unset( $item );
			$items = AccessPreview::menu_rules( $role, $profile, $items );
		}

		$manifest = self::manifest( $tab, $role, $items );
		require \EditorMenuWidgetAccess\PLUGIN_DIR . 'templates/admin/settings.php';
	}

	public static function areas() {
		return array(
			'appearance'  => __( 'Entire Appearance menu', 'editor-menu-and-widget-access' ),
			'themes'      => __( 'Themes', 'editor-menu-and-widget-access' ),
			'customize'   => __( 'Customize, Header and Background', 'editor-menu-and-widget-access' ),
			'widgets'     => __( 'Widgets', 'editor-menu-and-widget-access' ),
			'menus'       => __( 'Menus / Navigation', 'editor-menu-and-widget-access' ),
			'site_editor' => __( 'Site Editor / Design', 'editor-menu-and-widget-access' ),
			'identity'    => __( 'Identity', 'editor-menu-and-widget-access' ),
			'styles'      => __( 'Styles', 'editor-menu-and-widget-access' ),
			'patterns'    => __( 'Patterns', 'editor-menu-and-widget-access' ),
			'templates'   => __( 'Templates', 'editor-menu-and-widget-access' ),
			'fonts'       => __( 'Font Library', 'editor-menu-and-widget-access' ),
		);
	}

	public static function diagnostics( $config ) {
		global $wp_version;

		return wp_json_encode(
			array(
				'plugin'         => \EditorMenuWidgetAccess\VERSION,
				'wordpress'      => $wp_version,
				'php'            => PHP_VERSION,
				'woocommerce'    => defined( 'WC_VERSION' ) ? WC_VERSION : null,
				'theme'          => wp_get_theme()->get_stylesheet(),
				'block_theme'    => wp_is_block_theme(),
				'multisite'      => is_multisite(),
				'selected_roles' => $config['roles'],
				'legacy_backup'  => is_array( get_option( Migrations::BACKUP ) ),
				'hpos'           => class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ? \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() : null,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}
}
