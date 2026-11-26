<?php
/** Help, export/import and reversible settings changes. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\Admin\SettingsPage;
use EditorMenuWidgetAccess\Settings;
use EditorMenuWidgetAccess\RolePermissions;

$emwa_history      = get_option( Settings::HISTORY, array() );
$emwa_role_history = get_option( RolePermissions::HISTORY, array() );
$emwa_date_format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<h2><?php esc_html_e( 'Settings safety', 'editor-menu-and-widget-access' ); ?></h2>
<p><?php esc_html_e( 'Activation and the first request after upgrading reset historical theme-capability grants on built-in WordPress content roles. Missing Administrator theme-management grants are restored only when legacy settings selected Administrator. Changed roles are backed up first. Deactivation ends temporary grants and restrictions. Ordinary uninstall preserves configuration.', 'editor-menu-and-widget-access' ); ?></p>
<p><?php esc_html_e( 'Cleanup covers edit_theme_options, edit_themes, update_themes, delete_themes, install_themes and upload_themes. Explicit denials and unrelated permissions are preserved. Custom roles have no WordPress-defined defaults and are left unchanged. WooCommerce grants Shop Manager Appearance access by default; that permission is retained. Other permission tools may independently grant access.', 'editor-menu-and-widget-access' ); ?></p>

<p><?php esc_html_e( 'Keep a database backup before downgrading. Legacy options are preserved exactly as they were at upgrade; changes made in version 4 are not written back into the older format.', 'editor-menu-and-widget-access' ); ?></p>
<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'emwa_export', admin_url( 'admin-post.php' ) ), 'emwa_export' ) ); ?>"><?php esc_html_e( 'Export settings', 'editor-menu-and-widget-access' ); ?></a></p>
<h2><?php esc_html_e( 'Import settings', 'editor-menu-and-widget-access' ); ?></h2>
<p><?php esc_html_e( 'Paste a settings export below. Imported profiles replace matching profiles; other profiles and selected roles are retained. A backup is saved first.', 'editor-menu-and-widget-access' ); ?></p>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="emwa-edit-form">
	<?php wp_nonce_field( 'emwa_save' ); ?>
	<input type="hidden" name="action" value="emwa_save"><input type="hidden" name="intent" value="import"><input type="hidden" name="tab" value="help">
	<input type="hidden" name="revision" value="<?php echo esc_attr( Settings::revision( $config ) ); ?>">
	<label class="screen-reader-text" for="emwa-import"><?php esc_html_e( 'Settings JSON', 'editor-menu-and-widget-access' ); ?></label>
	<textarea id="emwa-import" name="import_json" rows="6" class="large-text code" dir="ltr" <?php disabled( ! $writable ); ?>></textarea>
	<p><button class="button" type="submit" <?php disabled( ! $writable ); ?>><?php esc_html_e( 'Import and merge profiles', 'editor-menu-and-widget-access' ); ?></button></p>
	<input type="hidden" name="complete" value="yes">
</form>
<?php if ( is_array( $emwa_history ) && $emwa_history ) : ?>
<h2><?php esc_html_e( 'Restore a settings backup', 'editor-menu-and-widget-access' ); ?></h2>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<?php wp_nonce_field( 'emwa_save' ); ?>
	<input type="hidden" name="action" value="emwa_save"><input type="hidden" name="intent" value="restore"><input type="hidden" name="tab" value="help">
	<input type="hidden" name="revision" value="<?php echo esc_attr( Settings::revision( $config ) ); ?>">
	<label for="emwa-backup"><?php esc_html_e( 'Saved configuration', 'editor-menu-and-widget-access' ); ?></label>
	<select id="emwa-backup" name="backup" <?php disabled( ! $writable ); ?>>
	<?php
	foreach ( array_reverse( $emwa_history ) as $emwa_entry ) :
		if ( ! is_array( $emwa_entry ) || ! isset( $emwa_entry['settings'], $emwa_entry['time'] ) || ! is_int( $emwa_entry['time'] ) || is_wp_error( Settings::validate( $emwa_entry['settings'] ) ) ) {
			continue; }
		?>
		<option value="<?php echo esc_attr( Settings::revision( $emwa_entry['settings'] ) ); ?>"><?php echo esc_html( wp_date( $emwa_date_format, $emwa_entry['time'] ) ); ?></option>
	<?php endforeach; ?>
	</select>
	<button type="submit" class="button" data-emwa-confirm="restore" <?php disabled( ! $writable ); ?>><?php esc_html_e( 'Restore backup', 'editor-menu-and-widget-access' ); ?></button>
	<input type="hidden" name="complete" value="yes">
</form>
<?php endif; ?>
<?php if ( current_user_can( 'edit_users' ) && is_array( $emwa_role_history ) && $emwa_role_history ) : ?>
<h2><?php esc_html_e( 'Role permission reset backups', 'editor-menu-and-widget-access' ); ?></h2>
<p><?php esc_html_e( 'The ten most recent role-capability snapshots are kept separately from settings backups. Download them before restoring a capability with a role-management tool; restoring an entire snapshot automatically could overwrite permissions changed by another plugin.', 'editor-menu-and-widget-access' ); ?></p>
<ul>
	<?php foreach ( $emwa_role_history as $emwa_entry ) : ?>
		<?php if ( is_array( $emwa_entry ) && isset( $emwa_entry['role'], $emwa_entry['created'] ) && is_string( $emwa_entry['role'] ) && is_int( $emwa_entry['created'] ) ) : ?>
			<li><bdi><?php echo esc_html( $emwa_entry['role'] ); ?></bdi> - <?php echo esc_html( wp_date( $emwa_date_format, $emwa_entry['created'] ) ); ?></li>
		<?php endif; ?>
	<?php endforeach; ?>
</ul>
<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'emwa_export_role_permissions', admin_url( 'admin-post.php' ) ), 'emwa_export_role_permissions' ) ); ?>"><?php esc_html_e( 'Download role permission backups', 'editor-menu-and-widget-access' ); ?></a></p>
<?php endif; ?>
<h2><?php esc_html_e( 'Compatibility diagnostics', 'editor-menu-and-widget-access' ); ?></h2>
<p><?php esc_html_e( 'This summary omits account details, site URLs and secrets. Menu and toolbar rules use stable IDs; plugins still enforce their own permissions.', 'editor-menu-and-widget-access' ); ?></p>
<label class="screen-reader-text" for="emwa-diagnostics"><?php esc_html_e( 'Diagnostic summary', 'editor-menu-and-widget-access' ); ?></label>
<textarea id="emwa-diagnostics" rows="12" class="large-text code" dir="ltr" readonly><?php echo esc_textarea( SettingsPage::diagnostics( $config ) ); ?></textarea>
<p><button type="button" class="button" data-emwa-copy="emwa-diagnostics" hidden><?php esc_html_e( 'Copy diagnostics', 'editor-menu-and-widget-access' ); ?></button><span class="emwa-copy-status" role="status" aria-live="polite"></span></p>
<p><a href="https://wordpress.org/support/plugin/editor-menu-and-widget-access/"><?php esc_html_e( 'Support forum', 'editor-menu-and-widget-access' ); ?></a> · <a href="https://github.com/GuyPrimavera/editor-menu-and-widget-access"><?php esc_html_e( 'Documentation and source', 'editor-menu-and-widget-access' ); ?></a></p>
