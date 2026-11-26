<?php
/** Review and copy one complete saved profile. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\Admin\SettingsPage;
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\RolePolicy;

$emwa_copy_options = array();

foreach ( $roles as $emwa_key => $emwa_label ) {
	$emwa_key         = (string) $emwa_key;
	$emwa_target_role = get_role( $emwa_key );

	if ( $emwa_key !== $role && $emwa_target_role && empty( $emwa_target_role->capabilities['manage_options'] ) ) {
		$emwa_copy_options[ $emwa_key ] = translate_user_role( $emwa_label );
	}
}
?>
<?php if ( isset( $config['profiles'][ $role ] ) && $emwa_copy_options ) : ?>
<div class="emwa-section">
	<h2><?php esc_html_e( 'Copy this role’s profile', 'editor-menu-and-widget-access' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Copy the saved Appearance, admin menu and toolbar settings to another role. The destination role’s profile is replaced; its selected or paused status stays the same. Stored WordPress role capabilities are unchanged.', 'editor-menu-and-widget-access' ); ?></p>
	<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="emwa-copy-picker">
		<input type="hidden" name="page" value="<?php echo esc_attr( SettingsPage::SLUG ); ?>">
		<input type="hidden" name="tab" value="roles">
		<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>">
		<label for="emwa-copy-target"><?php esc_html_e( 'Destination role', 'editor-menu-and-widget-access' ); ?></label>
		<select id="emwa-copy-target" name="copy_target" required>
			<option value=""><?php esc_html_e( 'Choose a role', 'editor-menu-and-widget-access' ); ?></option>
			<?php foreach ( $emwa_copy_options as $emwa_key => $emwa_label ) : ?>
				<option value="<?php echo esc_attr( $emwa_key ); ?>" <?php selected( $copy_target, $emwa_key ); ?>><?php echo esc_html( $emwa_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button" type="submit"><?php esc_html_e( 'Review copy', 'editor-menu-and-widget-access' ); ?></button>
	</form>
	<?php if ( $copy_target ) : ?>
		<?php
		$emwa_before = $config['profiles'][ $copy_target ] ?? Settings::profile( $copy_target );
		$emwa_after  = $config['profiles'][ $role ];
		$emwa_yes    = __( 'Yes', 'editor-menu-and-widget-access' );
		$emwa_no     = __( 'No', 'editor-menu-and-widget-access' );

		$emwa_counts = static function ( $profile, $key ) {
			if ( 'hidden_appearance' === $key ) {
				$hidden = array_filter(
					Settings::AREAS,
					static function ( $area ) use ( $profile ) {
						return RolePolicy::appearance_hidden( $profile, $area );
					}
				);

				return count( $hidden );
			}

			return count( array_filter( $profile[ $key ] ) );
		};
	?>
		<table class="widefat striped emwa-summary">
			<thead><tr><th scope="col"><?php esc_html_e( 'Setting', 'editor-menu-and-widget-access' ); ?></th><th scope="col"><?php esc_html_e( 'Current destination', 'editor-menu-and-widget-access' ); ?></th><th scope="col"><?php esc_html_e( 'After copy', 'editor-menu-and-widget-access' ); ?></th></tr></thead>
			<tbody>
				<tr><th scope="row"><?php esc_html_e( 'Appearance grant', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( $emwa_before['grant_appearance'] ? $emwa_yes : $emwa_no ); ?></td><td><?php echo esc_html( $emwa_after['grant_appearance'] ? $emwa_yes : $emwa_no ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Customizer restriction', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( $emwa_before['restrict_customizer'] ? $emwa_yes : $emwa_no ); ?></td><td><?php echo esc_html( $emwa_after['restrict_customizer'] ? $emwa_yes : $emwa_no ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Hidden Appearance areas', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( number_format_i18n( $emwa_counts( $emwa_before, 'hidden_appearance' ) ) ); ?></td><td><?php echo esc_html( number_format_i18n( $emwa_counts( $emwa_after, 'hidden_appearance' ) ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Hidden admin menu rules', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( number_format_i18n( count( array_filter( array_column( $emwa_before['menus'], 'hidden' ) ) ) ) ); ?></td><td><?php echo esc_html( number_format_i18n( count( array_filter( array_column( $emwa_after['menus'], 'hidden' ) ) ) ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Hidden toolbar rules', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( number_format_i18n( $emwa_counts( $emwa_before, 'toolbar' ) ) ); ?></td><td><?php echo esc_html( number_format_i18n( $emwa_counts( $emwa_after, 'toolbar' ) ) ); ?></td></tr>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Review both profiles before copying. The settings backup can restore the destination profile if needed.', 'editor-menu-and-widget-access' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'emwa_copy_profile_' . $role . '_' . $copy_target ); ?>
			<input type="hidden" name="action" value="emwa_copy_profile">
			<input type="hidden" name="source" value="<?php echo esc_attr( $role ); ?>">
			<input type="hidden" name="target" value="<?php echo esc_attr( $copy_target ); ?>">
			<input type="hidden" name="revision" value="<?php echo esc_attr( Settings::revision( $config ) ); ?>">
			<button type="submit" class="button" data-emwa-confirm="copy" <?php disabled( ! $writable ); ?>><?php esc_html_e( 'Copy reviewed profile', 'editor-menu-and-widget-access' ); ?></button>
			<input type="hidden" name="complete" value="yes">
		</form>
	<?php endif; ?>
</div>
<?php endif; ?>
