<?php
/** Settings screen. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\Admin\SettingsPage;
use EditorMenuWidgetAccess\Settings;

?>
<div class="wrap emwa-settings">
	<h1><?php esc_html_e( 'Editor Access', 'editor-menu-and-widget-access' ); ?></h1>
	<p><?php esc_html_e( 'Give selected roles appearance access and simplify their WordPress workspace.', 'editor-menu-and-widget-access' ); ?></p>
	<?php if ( ! $roles ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'No roles are available to configure. Settings and backups are retained below.', 'editor-menu-and-widget-access' ); ?></p></div>
	<?php endif; ?>
	<?php if ( is_array( $notice ) ) : ?>
		<div class="notice <?php echo ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success'; ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
			<?php if ( ! empty( $notice['details'] ) && is_array( $notice['details'] ) ) : ?>
				<p><?php esc_html_e( 'Reset verification:', 'editor-menu-and-widget-access' ); ?>
					<?php esc_html_e( 'Stored Appearance permission', 'editor-menu-and-widget-access' ); ?>:
					<?php echo esc_html( $notice['details']['before']['stored'] ? __( 'Yes', 'editor-menu-and-widget-access' ) : __( 'No', 'editor-menu-and-widget-access' ) ); ?> → <?php echo esc_html( $notice['details']['after']['stored'] ? __( 'Yes', 'editor-menu-and-widget-access' ) : __( 'No', 'editor-menu-and-widget-access' ) ); ?>;
					<?php esc_html_e( 'Active plugin Appearance grant', 'editor-menu-and-widget-access' ); ?>:
					<?php echo esc_html( $notice['details']['before']['plugin'] ? __( 'Yes', 'editor-menu-and-widget-access' ) : __( 'No', 'editor-menu-and-widget-access' ) ); ?> → <?php echo esc_html( $notice['details']['after']['plugin'] ? __( 'Yes', 'editor-menu-and-widget-access' ) : __( 'No', 'editor-menu-and-widget-access' ) ); ?>.
					<a href="<?php echo esc_url( SettingsPage::url( 'help', $role ) ); ?>"><?php esc_html_e( 'View backups', 'editor-menu-and-widget-access' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! $writable ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Settings cannot be safely edited. Stored data has been preserved. Export it and check the migration guidance before making changes.', 'editor-menu-and-widget-access' ); ?></p></div>
	<?php endif; ?>
	<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Editor Access settings', 'editor-menu-and-widget-access' ); ?>">
		<?php
		foreach ( array(
			'roles'   => __( 'Roles & Access', 'editor-menu-and-widget-access' ),
			'menus'   => __( 'Admin Menu', 'editor-menu-and-widget-access' ),
			'toolbar' => __( 'Toolbar', 'editor-menu-and-widget-access' ),
			'help'    => __( 'Help & Backups', 'editor-menu-and-widget-access' ),
		) as $emwa_key => $emwa_label ) :
			?>
			<a class="nav-tab <?php echo $tab === $emwa_key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( SettingsPage::url( $emwa_key, $role ) ); ?>" <?php echo $tab === $emwa_key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $emwa_label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php if ( in_array( $tab, array( 'menus', 'toolbar' ), true ) ) : ?>
		<form id="emwa-role-picker" method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="emwa-role-picker">
			<input type="hidden" name="page" value="<?php echo esc_attr( SettingsPage::SLUG ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<label for="emwa-role"><strong><?php esc_html_e( 'Role', 'editor-menu-and-widget-access' ); ?></strong></label>
			<select id="emwa-role" name="role">
				<?php foreach ( $roles as $emwa_key => $emwa_label ) : ?>
					<option value="<?php echo esc_attr( $emwa_key ); ?>" <?php selected( $role, $emwa_key ); ?>><?php echo esc_html( translate_user_role( $emwa_label ) ); ?><?php echo get_role( $emwa_key ) ? '' : ' - ' . esc_html__( 'unavailable', 'editor-menu-and-widget-access' ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Switch role', 'editor-menu-and-widget-access' ); ?></button>
			<?php if ( ! in_array( $role, $config['roles'], true ) ) : ?>
				<span class="description"><?php esc_html_e( 'This profile is inactive. Select the role on Roles & Access to apply it.', 'editor-menu-and-widget-access' ); ?></span>
			<?php endif; ?>
		</form>
	<?php endif; ?>
	<?php if ( 'help' !== $tab ) : ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="emwa-edit-form">
			<?php wp_nonce_field( 'emwa_save' ); ?>
			<input type="hidden" name="action" value="emwa_save">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>">
			<input type="hidden" name="revision" value="<?php echo esc_attr( Settings::revision( $config ) ); ?>">
			<input type="hidden" name="manifest" value="<?php echo esc_attr( $manifest[0] ); ?>">
			<input type="hidden" name="signature" value="<?php echo esc_attr( $manifest[1] ); ?>">
			<?php if ( 'roles' === $tab ) : ?>
				<input type="hidden" name="emwa_roles_ui" value="2">
			<?php elseif ( 'menus' === $tab ) : ?>
				<input type="hidden" name="emwa_appearance_ui" value="2">
			<?php endif; ?>
			<fieldset <?php disabled( ! $writable ); ?>>
				<legend class="screen-reader-text"><?php esc_html_e( 'Role settings', 'editor-menu-and-widget-access' ); ?></legend>
				<?php require \EditorMenuWidgetAccess\PLUGIN_DIR . 'templates/admin/' . ( 'roles' === $tab ? 'roles.php' : 'visibility.php' ); ?>
				<p class="submit">
					<button class="button button-primary" type="submit" name="intent" value="save"><?php esc_html_e( 'Save Changes', 'editor-menu-and-widget-access' ); ?></button>
					<button class="button" type="submit" name="intent" value="reset" data-emwa-confirm="reset"><?php esc_html_e( 'Reset settings for this role', 'editor-menu-and-widget-access' ); ?></button>
					<span class="emwa-dirty" role="status" aria-live="polite"></span>
				</p>
			</fieldset>
			<!-- Last field detects requests truncated by max_input_vars. -->
			<input type="hidden" name="complete" value="yes">
		</form>
		<?php if ( 'roles' === $tab ) : ?>
			<?php require \EditorMenuWidgetAccess\PLUGIN_DIR . 'templates/admin/role-reset.php'; ?>
			<?php require \EditorMenuWidgetAccess\PLUGIN_DIR . 'templates/admin/access-preview.php'; ?>
			<?php require \EditorMenuWidgetAccess\PLUGIN_DIR . 'templates/admin/profile-copy.php'; ?>
		<?php endif; ?>
	<?php else : ?>
		<?php require \EditorMenuWidgetAccess\PLUGIN_DIR . 'templates/admin/help.php'; ?>
	<?php endif; ?>
</div>
