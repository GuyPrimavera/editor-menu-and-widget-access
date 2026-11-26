<?php
/** Read-only access snapshot for the currently selected role. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\Admin\AccessPreview;

$emwa_preview = AccessPreview::for_role( $role );
$emwa_stored  = get_role( $role );
$emwa_yes     = __( 'Yes', 'editor-menu-and-widget-access' );
$emwa_no      = __( 'No', 'editor-menu-and-widget-access' );
?>
<div class="emwa-section">
	<h2><?php esc_html_e( 'Access preview', 'editor-menu-and-widget-access' ); ?></h2>
	<p class="description"><?php esc_html_e( 'This is a read-only snapshot of saved settings and the menus registered on this administrator screen. Other plugins may add role-specific items or make additional checks on their own pages.', 'editor-menu-and-widget-access' ); ?></p>
	<table class="widefat striped emwa-summary">
		<tbody>
			<tr><th scope="row"><?php esc_html_e( 'Stored Appearance permission', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( $emwa_stored && ! empty( $emwa_stored->capabilities['edit_theme_options'] ) ? $emwa_yes : $emwa_no ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Active plugin Appearance grant', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( in_array( $role, $config['roles'], true ) && ! empty( $profile['grant_appearance'] ) ? $emwa_yes : $emwa_no ); ?></td></tr>
			<?php if ( $emwa_preview['user'] ) : ?>
				<tr><th scope="row"><?php esc_html_e( 'Sample user', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( $emwa_preview['user']->display_name ); ?> (<?php echo esc_html( $emwa_preview['user']->user_login ); ?>)</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Effective Appearance permission for sample user', 'editor-menu-and-widget-access' ); ?></th><td><?php echo esc_html( $emwa_preview['appearance'] ? $emwa_yes : $emwa_no ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php if ( $emwa_preview['user'] ) : ?>
		<p class="description"><?php esc_html_e( 'The sample is one existing user with this role. Their other roles and user-specific permissions also apply. Menu results below are estimates based on the items registered for this administrator.', 'editor-menu-and-widget-access' ); ?></p>
		<?php if ( $emwa_preview['menus'] ) : ?>
			<details><summary><?php esc_html_e( 'Review top-level menu estimates', 'editor-menu-and-widget-access' ); ?></summary>
				<table class="widefat striped emwa-preview-menus">
					<thead><tr><th scope="col"><?php esc_html_e( 'Menu', 'editor-menu-and-widget-access' ); ?></th><th scope="col"><?php esc_html_e( 'Estimated status', 'editor-menu-and-widget-access' ); ?></th></tr></thead>
					<tbody><?php foreach ( $emwa_preview['menus'] as $emwa_item ) : ?>
						<tr><td><?php echo esc_html( $emwa_item['label'] ); ?></td><td><?php echo esc_html( $emwa_item['hidden'] ? __( 'Hidden by this plugin', 'editor-menu-and-widget-access' ) : ( false === $emwa_item['allowed'] ? __( 'User lacks menu capability', 'editor-menu-and-widget-access' ) : ( true === $emwa_item['allowed'] ? __( 'Potentially visible', 'editor-menu-and-widget-access' ) : __( 'Capability unknown', 'editor-menu-and-widget-access' ) ) ) ); ?></td></tr>
					<?php endforeach; ?></tbody>
				</table>
			</details>
		<?php endif; ?>
	<?php elseif ( current_user_can( 'list_users' ) ) : ?>
		<p class="description"><?php esc_html_e( 'No existing user has this role, so effective user permissions cannot be checked yet.', 'editor-menu-and-widget-access' ); ?></p>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'You need permission to list users to preview effective access.', 'editor-menu-and-widget-access' ); ?></p>
	<?php endif; ?>
</div>
