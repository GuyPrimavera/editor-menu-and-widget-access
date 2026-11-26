<?php
/** Localisation test menus; only install on a disposable test site. */
add_action( 'admin_menu', static function () {
	foreach ( array( 'emwa-l10n-fr' => 'Édition', 'emwa-l10n-de' => 'Überblick', 'emwa-l10n-tr' => 'İşlemler', 'emwa-l10n-ar' => 'إعدادات المتجر' ) as $slug => $label ) {
		add_menu_page( $label, $label, 'edit_posts', $slug, static function () {} );
	}
} );
