<?php
/** Menu and Customizer visibility using WordPress hooks. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class MenuVisibility {
	/** The Site Editor has its own navigation, separate from WordPress's PHP menus. */
	public static function site_editor_hidden_areas( $user ) {
		$hidden = array();

		foreach ( array(
			'menus'     => 'nav-menus.php',
			'identity'  => 'site-editor.php?p=/identity',
			'styles'    => 'site-editor.php?p=/styles',
			'patterns'  => 'site-editor.php?p=/pattern',
			'templates' => 'site-editor.php?p=/template',
		) as $area => $route ) {
			if ( RolePolicy::hides_menu( $user, 'themes.php', $route ) ) {
				$hidden[] = $area;
			}
		}

		return $hidden;
	}

	public static function site_editor_assets( $hook ) {
		if ( 'site-editor.php' === $hook && self::site_editor_hidden_areas( wp_get_current_user() ) ) {
			$version = \EditorMenuWidgetAccess\VERSION . '.' . substr( md5_file( \EditorMenuWidgetAccess\PLUGIN_DIR . 'assets/admin/site-editor.css' ), 0, 12 );
			wp_enqueue_style( 'emwa-site-editor', plugins_url( 'assets/admin/site-editor.css', \EditorMenuWidgetAccess\PLUGIN_FULL_PATH ), array(), $version );
		}
	}

	public static function site_editor_classes( $classes ) {
		global $pagenow;

		if ( 'site-editor.php' === $pagenow ) {
			foreach ( self::site_editor_hidden_areas( wp_get_current_user() ) as $area ) {
				$classes .= ' emwa-hide-site-' . $area;
			}
		}

		return $classes;
	}

	public static function apply() {
		global $menu, $submenu;

		$user = wp_get_current_user();

		if ( ! RolePolicy::profiles( $user ) ) {
			return;
		}

		foreach ( (array) $submenu as $parent => $children ) {
			foreach ( (array) $children as $item ) {
				if ( is_array( $item ) && isset( $item[2] ) && is_string( $item[2] ) && RolePolicy::hides_menu( $user, (string) $parent, $item[2] ) ) {
					remove_submenu_page( $parent, $item[2] );
				}
			}
		}

		foreach ( (array) $menu as $item ) {
			if ( is_array( $item ) && isset( $item[2] ) && is_string( $item[2] ) && RolePolicy::hides_menu( $user, '', $item[2] ) ) {
				remove_menu_page( $item[2] );
			}
		}
	}

	public static function customizer_components( $components ) {
		$user = wp_get_current_user();

		return array_filter(
			(array) $components,
			static function ( $component ) use ( $user ) {
				return ! ( 'widgets' === $component && RolePolicy::hides_area( $user, 'widgets' ) ) && ! ( 'nav_menus' === $component && RolePolicy::hides_area( $user, 'menus' ) );
			}
		);
	}
}
