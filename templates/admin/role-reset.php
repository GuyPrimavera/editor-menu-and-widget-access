<?php
/** Deliberate role-capability reset, outside the ordinary settings form. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\RolePermissions;
use EditorMenuWidgetAccess\Settings;

$emwa_stored_role = get_role( $role );
$emwa_core_role   = RolePermissions::has_core_default( $role );
?>
<div class="emwa-permission-reset">
	<h2><?php esc_html_e( 'Reset Appearance permissions', 'editor-menu-and-widget-access' ); ?></h2>
	<?php if ( $emwa_core_role ) : ?>
		<p><?php esc_html_e( 'For this built-in WordPress role, reset removes a stored edit_theme_options grant and turns off this plugin’s appearance grant and Customizer restriction. Other capabilities, menu rules and roles are unchanged. If another plugin added the grant, it may add it again. A copy of the role’s capabilities is saved before removal.', 'editor-menu-and-widget-access' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'WordPress has no default permissions for this plugin-defined role. Reset turns off this plugin’s appearance grant and Customizer restriction, while preserving capabilities supplied by WooCommerce or other plugins. WooCommerce grants Shop Manager Appearance access by default.', 'editor-menu-and-widget-access' ); ?></p>
	<?php endif; ?>
	<?php if ( $emwa_stored_role && empty( $emwa_stored_role->capabilities['manage_options'] ) && current_user_can( 'edit_users' ) ) : ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="emwa-reset-form">
			<?php wp_nonce_field( 'emwa_reset_role_permissions_' . $role ); ?>
			<input type="hidden" name="action" value="emwa_reset_role_permissions">
			<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>">
			<input type="hidden" name="revision" value="<?php echo esc_attr( Settings::revision( $config ) ); ?>">
			<input type="hidden" name="role_revision" value="<?php echo esc_attr( RolePermissions::revision( $emwa_stored_role ) ); ?>">
			<button type="submit" class="button" data-emwa-confirm="permissions" <?php disabled( ! $writable ); ?>><?php echo $emwa_core_role ? esc_html__( 'Reset permissions to WordPress defaults', 'editor-menu-and-widget-access' ) : esc_html__( 'Reset this plugin’s permissions', 'editor-menu-and-widget-access' ); ?></button>
			<input type="hidden" name="complete" value="yes">
		</form>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'This role is unavailable or cannot be reset with your current permissions.', 'editor-menu-and-widget-access' ); ?></p>
	<?php endif; ?>
</div>
