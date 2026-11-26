<?php
/** Additive runtime grants and supported restrictions. @package EditorMenuWidgetAccess */
namespace EditorMenuWidgetAccess;

defined( 'ABSPATH' ) || exit;

final class Capabilities {
	/** Never modify stored roles or replace an explicit allow/deny decision. */
	public static function grant( $allcaps, $required, $args, $user ) {
		if ( in_array( 'edit_theme_options', $required, true ) && ! array_key_exists( 'edit_theme_options', $allcaps ) && empty( $allcaps['manage_options'] ) && RolePolicy::grants_appearance( $user ) ) {
			$allcaps['edit_theme_options'] = true;
		}

		return $allcaps;
	}

	/** Customize has a dedicated meta capability, unlike widgets and Site Editor. */
	public static function restrict( $caps, $cap, $user_id ) {
		if ( 'customize' !== $cap ) {
			return $caps;
		}

		foreach ( RolePolicy::profiles( get_userdata( $user_id ) ) as $profile ) {
			if ( $profile['restrict_customizer'] ) {
				return array( 'do_not_allow' );
			}
		}

		return $caps;
	}
}
