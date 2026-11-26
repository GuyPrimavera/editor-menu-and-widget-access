<?php
/** Role selection and appearance permissions. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\Admin\SettingsPage;

?>
<h2><?php esc_html_e( 'Roles using Editor Access', 'editor-menu-and-widget-access' ); ?></h2>
<p class="description"><?php esc_html_e( 'Tick the roles that should use these settings, then save. Use Edit access beside a role to configure its permissions. Unticking a role pauses its profile without deleting it. Administrators, super admins and users with Manage Options remain unaffected.', 'editor-menu-and-widget-access' ); ?></p>
<div class="emwa-role-grid emwa-role-list">
	<?php foreach ( $roles as $emwa_key => $emwa_label ) : ?>
		<?php $emwa_key = (string) $emwa_key; ?>
		<div class="emwa-role-card <?php echo $role === $emwa_key ? 'emwa-role-card-active' : ''; ?>">
			<label><input type="checkbox" name="selected_roles[]" value="<?php echo esc_attr( $emwa_key ); ?>" <?php checked( in_array( $emwa_key, $config['roles'], true ) ); ?>> <?php echo esc_html( translate_user_role( $emwa_label ) ); ?></label>
			<a href="<?php echo esc_url( SettingsPage::url( 'roles', $emwa_key ) ); ?>" <?php echo $role === $emwa_key ? 'aria-current="page"' : ''; ?>><?php echo $role === $emwa_key ? esc_html__( 'Editing access', 'editor-menu-and-widget-access' ) : esc_html__( 'Edit access', 'editor-menu-and-widget-access' ); ?></a>
			<?php if ( ! get_role( $emwa_key ) ) : ?>
				<span class="description"><?php esc_html_e( 'Role unavailable; saved profile retained', 'editor-menu-and-widget-access' ); ?></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
<?php
// translators: %s: name of the WordPress role being configured.
$emwa_heading = sprintf( __( 'Appearance permissions for %s', 'editor-menu-and-widget-access' ), translate_user_role( $roles[ $role ] ) );
?>
<h2><?php echo esc_html( $emwa_heading ); ?></h2>
<?php if ( ! in_array( $role, $config['roles'], true ) ) : ?>
	<p class="description"><?php esc_html_e( 'This profile is inactive until you tick the role above and save.', 'editor-menu-and-widget-access' ); ?></p>
<?php endif; ?>
<p><label><input type="checkbox" name="grant_appearance" value="1" <?php checked( $profile['grant_appearance'] ); ?>> <?php esc_html_e( 'Add appearance access while this plugin is active', 'editor-menu-and-widget-access' ); ?></label></p>
<p class="description"><?php esc_html_e( 'WordPress uses the same capability for widgets, menus, Site Editor and some theme options. Existing permissions and explicit denials from WordPress or other plugins are respected. Unchecking this does not revoke permissions granted elsewhere or retained from an older version.', 'editor-menu-and-widget-access' ); ?></p>
<?php $emwa_stored_role = get_role( $role ); ?>
<?php if ( $emwa_stored_role && ! empty( $emwa_stored_role->capabilities['edit_theme_options'] ) ) : ?>
	<?php if ( 'shop_manager' === $role ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'WooCommerce grants Shop Manager this Appearance permission by default. It remains after this plugin is deactivated.', 'editor-menu-and-widget-access' ); ?></p></div>
	<?php elseif ( \EditorMenuWidgetAccess\RolePermissions::has_core_default( $role ) ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'This built-in role has a stored Appearance permission beyond its WordPress default. Turning off the plugin grant above will not remove it; use the reset action below if you want the default.', 'editor-menu-and-widget-access' ); ?></p></div>
	<?php else : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'This plugin-defined role has a stored Appearance permission. Its owning plugin controls that permission, so the reset below leaves it intact.', 'editor-menu-and-widget-access' ); ?></p></div>
	<?php endif; ?>
<?php endif; ?>
<p><label><input type="checkbox" name="restrict_customizer" value="1" <?php checked( $profile['restrict_customizer'] ); ?>> <?php esc_html_e( 'Deny direct access to the WordPress Customizer', 'editor-menu-and-widget-access' ); ?></label></p>
<p class="description"><?php esc_html_e( 'This optional permission restriction uses WordPress capability checks and also prevents Customizer saves. It does not restrict widgets, Site Editor or third-party settings pages.', 'editor-menu-and-widget-access' ); ?></p>
<p class="description"><?php esc_html_e( 'To hide Appearance links, use the Admin Menu tab for this role.', 'editor-menu-and-widget-access' ); ?></p>
<p class="description"><?php esc_html_e( 'For users with multiple selected roles, hiding or a Customizer restriction in any matching profile takes precedence. Any matching profile may add appearance access.', 'editor-menu-and-widget-access' ); ?></p>
