<?php
/** Stable menu identities, independent of translated labels. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

use EditorMenuWidgetAccess\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class Routes {
	/** Normalize only known volatile parameters; retain plugin route punctuation. */
	public static function normalize( $route ) {
		$route = html_entity_decode( (string) $route, ENT_QUOTES, 'UTF-8' );

		if ( 'customize.php' === strtok( $route, '?' ) ) {
			$route = remove_query_arg( 'return', $route );
		}

		return WooCommerce::canonical_menu( $route );
	}

	/** A child belongs to its parent even when another menu reuses its slug. */
	public static function id( $parent_slug, $slug ) {
		return 'm_' . hash( 'sha256', wp_json_encode( array( self::normalize( $parent_slug ), self::normalize( $slug ) ) ) );
	}

	/** Identify appearance screens without relying on menu positions. */
	public static function appearance_area( $parent_slug, $slug ) {
		if ( '' === $parent_slug && 'themes.php' === $slug ) {
			return 'appearance';
		}

		if ( 'themes.php' !== $parent_slug ) {
			return '';
		}

		$query = array();
		wp_parse_str( (string) wp_parse_url( html_entity_decode( $slug, ENT_QUOTES, 'UTF-8' ), PHP_URL_QUERY ), $query );

		// A plugin page on a core script is not the core screen's visibility control.
		if ( isset( $query['page'] ) ) {
			return '';
		}

		$path  = strtok( html_entity_decode( $slug, ENT_QUOTES, 'UTF-8' ), '?' );
		$areas = array(
			'themes.php'        => 'themes',
			'widgets.php'       => 'widgets',
			'nav-menus.php'     => 'menus',
			'customize.php'     => 'customize',
			'custom-header'     => 'customize',
			'custom-background' => 'customize',
			'font-library.php'  => 'fonts',
			'site-editor.php'   => 'site_editor',
		);

		if ( 'site-editor.php' === $path ) {
			$route      = $query['p'] ?? $query['path'] ?? $query['postType'] ?? '';
			$area       = is_string( $route ) ? explode( '/', trim( $route, '/' ) )[0] : '';
			$site_areas = array(
				'identity'         => 'identity',
				'styles'           => 'styles',
				'stylebook'        => 'styles',
				'wp_global_styles' => 'styles',
				'navigation'       => 'menus',
				'wp_navigation'    => 'menus',
				'pattern'          => 'patterns',
				'patterns'         => 'patterns',
				'wp_block'         => 'patterns',
				'wp_template_part' => 'patterns',
				'template'         => 'templates',
				'wp_template'      => 'templates',
			);

			if ( isset( $site_areas[ $area ] ) ) {
				return $site_areas[ $area ];
			}
		}

		if ( 'edit.php?post_type=wp_block' === $slug ) {
			return 'patterns';
		}

		if ( 'edit.php?post_type=wp_navigation' === $slug ) {
			return 'menus';
		}

		return $areas[ $path ] ?? '';
	}

	/** Bound identity strings without treating them as WordPress keys. */
	public static function valid_string( $value, $max = 2048, $allow_empty = false ) {
		return is_string( $value ) && strlen( $value ) <= $max && ( $allow_empty || '' !== $value ) && ! preg_match( '/[\x00-\x1f\x7f]/', $value );
	}
}
