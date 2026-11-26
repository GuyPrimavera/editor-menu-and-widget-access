<?php
/** Toolbar rules applied directly by node ID. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class ToolbarVisibility {
	/** Match only links to this site's WordPress Customizer, including theme-added nodes. */
	private static function is_customizer_link( $href ) {
		if ( ! is_string( $href ) || '' === $href ) {
			return false;
		}

		$link = wp_parse_url( html_entity_decode( $href, ENT_QUOTES, 'UTF-8' ) );
		$core = wp_parse_url( admin_url( 'customize.php' ) );

		if ( ! is_array( $link ) || ! is_array( $core ) || ( $link['path'] ?? '' ) !== ( $core['path'] ?? '' ) ) {
			return false;
		}

		return ! isset( $link['host'] ) || strtolower( $link['host'] ) === strtolower( $core['host'] ?? '' );
	}

	public static function apply() {
		global $wp_admin_bar;

		if ( ! $wp_admin_bar instanceof \WP_Admin_Bar ) {
			return;
		}

		$hide_customizer = false;
		$mapping         = array(
			'themes'      => 'themes',
			'customize'   => 'customize',
			'widgets'     => 'widgets',
			'menus'       => 'menus',
			'site_editor' => 'site-editor',
		);

		foreach ( RolePolicy::profiles( wp_get_current_user() ) as $profile ) {
			$hide_customizer = $hide_customizer || ! empty( $profile['hidden_appearance']['customize'] ) || ! empty( $profile['restrict_customizer'] );

			foreach ( $mapping as $area => $id ) {
				if ( RolePolicy::appearance_hidden( $profile, $area ) ) {
					$wp_admin_bar->remove_node( $id );
				}
			}

			if ( 'both' !== $profile['toolbar_scope'] && ( is_admin() ? 'admin' : 'frontend' ) !== $profile['toolbar_scope'] ) {
				continue;
			}

			foreach ( $profile['toolbar'] as $id => $hidden ) {
				if ( $hidden ) {
					$wp_admin_bar->remove_node( $id );
				}
			}
		}

		if ( $hide_customizer ) {
			foreach ( (array) $wp_admin_bar->get_nodes() as $node ) {
				if ( self::is_customizer_link( $node->href ?? '' ) ) {
					$wp_admin_bar->remove_node( $node->id );
				}
			}
		}
	}
}
