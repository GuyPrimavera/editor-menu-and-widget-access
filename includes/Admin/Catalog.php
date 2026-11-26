<?php
/** Read current menus/nodes and retain unavailable saved rules. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess\Admin;

use EditorMenuWidgetAccess\Routes;

defined( 'ABSPATH' ) || exit;

final class Catalog {
	private static $toolbar_snapshot;

	/** Read before WordPress binds toolbar nodes for output. */
	public static function capture_toolbar() {
		global $wp_admin_bar;

		self::$toolbar_snapshot = $wp_admin_bar instanceof \WP_Admin_Bar ? (array) $wp_admin_bar->get_nodes() : array();
	}

	/** Strip markup and badges before displaying labels from third-party plugins. */
	public static function label( $title, $fallback ) {
		if ( ! is_scalar( $title ) && ! ( is_object( $title ) && method_exists( $title, '__toString' ) ) ) {
			return $fallback;
		}

		$title = preg_replace( '~<span\b[^>]*class=["\'][^"\']*(?:count|update-plugins)[^"\']*["\'][^>]*>.*?</span>~is', '', (string) $title );
		$title = trim( wp_strip_all_tags( $title ) );

		return '' !== $title ? html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ) : $fallback;
	}

	public static function menus() {
		global $menu, $submenu;

		$items = array();

		foreach ( (array) $menu as $item ) {
			if ( ! is_array( $item ) || empty( $item[0] ) || ! isset( $item[1], $item[2] ) || ! is_string( $item[2] ) ) {
				continue;
			}

			$slug         = $item[2];
			$id           = Routes::id( '', $slug );
			$items[ $id ] = array(
				'parent'     => '',
				'slug'       => $slug,
				'label'      => self::label( $item[0], $slug ),
				'capability' => $item[1],
				'depth'      => 0,
				'group'      => $id,
				'available'  => true,
			);

			foreach ( (array) ( $submenu[ $slug ] ?? array() ) as $child ) {
				if ( ! is_array( $child ) || ! isset( $child[0], $child[1], $child[2] ) || '' === $child[0] || ! is_string( $child[2] ) ) {
					continue;
				}

				if ( $child[2] === $slug && $child[1] === $item[1] && self::label( $child[0], $slug ) === self::label( $item[0], $slug ) ) {
					continue;
				}

				$items[ Routes::id( $slug, $child[2] ) ] = array(
					'parent'     => $slug,
					'slug'       => $child[2],
					'label'      => self::label( $child[0], $child[2] ),
					'capability' => $child[1],
					'depth'      => 1,
					'group'      => $id,
					'available'  => true,
				);
			}
		}

		return $items;
	}

	public static function menu_rules( $profile ) {
		$items = self::menus();

		foreach ( $profile['menus'] as $id => $rule ) {
			if ( isset( $items[ $id ] ) ) {
				continue;
			}

			$matched = false;

			if ( '*' === $rule['parent'] ) {
				foreach ( $items as $item ) {
					if ( Routes::normalize( $item['slug'] ) === Routes::normalize( $rule['slug'] ) ) {
						$matched = true;
						break;
					}
				}
			}

			$items[ $id ] = array(
				'parent'     => $rule['parent'],
				'slug'       => $rule['slug'],
				'label'      => $rule['slug'],
				'capability' => '',
				'depth'      => 0,
				'group'      => $id,
				'available'  => $matched,
				'legacy'     => '*' === $rule['parent'],
			);
		}

		return $items;
	}

	public static function toolbar() {
		global $wp_admin_bar;

		$nodes = null !== self::$toolbar_snapshot ? self::$toolbar_snapshot : ( $wp_admin_bar instanceof \WP_Admin_Bar ? (array) $wp_admin_bar->get_nodes() : array() );
		$items = array();

		foreach ( $nodes as $node ) {
			if ( ! empty( $node->group ) ) {
				continue;
			}

			$parent = (string) $node->parent;
			$depth  = 0;
			$group  = $node->id;
			$seen   = array( $node->id => true );

			// Bounded traversal handles orphaned nodes or malformed plugin-generated cycles.
			while ( isset( $nodes[ $parent ] ) && ! isset( $seen[ $parent ] ) && $depth < 20 ) {
				$seen[ $parent ] = true;
				$group           = $parent;
				$parent          = (string) $nodes[ $parent ]->parent;
				++$depth;
			}

			$items[ $node->id ] = array(
				'label'     => self::label( $node->title, ucwords( str_replace( array( '-', '_' ), ' ', $node->id ) ) ),
				'depth'     => min( $depth, 3 ),
				'group'     => $group,
				'available' => true,
			);
		}

		if ( ! isset( $items['edit'] ) ) {
			$items['edit'] = array(
				'label'     => __( 'Edit content (frontend toolbar)', 'editor-menu-and-widget-access' ),
				'depth'     => 0,
				'group'     => 'edit',
				'available' => false,
			);
		}

		return $items;
	}

	public static function toolbar_rules( $profile ) {
		$items = self::toolbar();

		foreach ( $profile['toolbar'] as $id => $hidden ) {
			if ( ! isset( $items[ $id ] ) ) {
				$items[ $id ] = array(
					'label'     => $id,
					'depth'     => 0,
					'group'     => $id,
					'available' => false,
				);
			}
		}

		return $items;
	}
}
