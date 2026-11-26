<?php
/** Searchable visibility rules, progressively enhanced. @package EditorMenuWidgetAccess */
defined( 'ABSPATH' ) || exit;
use EditorMenuWidgetAccess\RolePolicy;
use EditorMenuWidgetAccess\Admin\SettingsPage;

?>
<?php if ( 'menus' === $tab ) : ?>
	<h2><?php esc_html_e( 'Appearance menu links', 'editor-menu-and-widget-access' ); ?></h2>
	<p><?php esc_html_e( 'Checked links are hidden for this role. Hiding a link does not deny direct access; available screens depend on the active theme and WordPress version.', 'editor-menu-and-widget-access' ); ?></p>
	<p class="description"><?php esc_html_e( 'Menus / Navigation, Identity, Styles, Patterns and Templates also hide their sidebar links inside the Site Editor. These settings do not prevent editing through other routes.', 'editor-menu-and-widget-access' ); ?></p>
	<div class="emwa-role-grid">
		<?php foreach ( SettingsPage::areas() as $emwa_area => $emwa_label ) : ?>
			<label><input type="checkbox" name="hidden_appearance[]" value="<?php echo esc_attr( $emwa_area ); ?>" <?php checked( RolePolicy::appearance_hidden( $profile, $emwa_area ) ); ?>> <?php echo esc_html( $emwa_label ); ?></label>
		<?php endforeach; ?>
	</div>
	<p class="description"><?php echo wp_is_block_theme() ? esc_html__( 'Your block theme uses Site Editor and block navigation. Classic Menus and Widgets appear only when the theme supports them.', 'editor-menu-and-widget-access' ) : esc_html__( 'Your classic theme controls which Menus, Widgets and Customizer screens are available.', 'editor-menu-and-widget-access' ); ?></p>
<?php endif; ?>
<h2><?php echo 'menus' === $tab ? esc_html__( 'Hide admin menu links', 'editor-menu-and-widget-access' ) : esc_html__( 'Hide toolbar items', 'editor-menu-and-widget-access' ); ?></h2>
<p><?php esc_html_e( 'Checked items are hidden for this role. Other plugins still control access permissions. Rules for unavailable items are retained until you explicitly change or reset them.', 'editor-menu-and-widget-access' ); ?></p>
<?php if ( 'menus' === $tab ) : ?>
	<p class="description"><?php esc_html_e( 'The default list shows items this role has permission to use, including links hidden by Editor Access so you can unhide them. Availability is estimated from the role permissions and saved profile; individual accounts and other plugins can differ.', 'editor-menu-and-widget-access' ); ?></p>
<?php endif; ?>
<?php if ( 'toolbar' === $tab ) : ?>
	<p><label for="emwa-scope"><?php esc_html_e( 'Apply toolbar rules on', 'editor-menu-and-widget-access' ); ?></label>
	<select name="toolbar_scope" id="emwa-scope">
		<?php
		foreach ( array(
			'both'     => __( 'Admin and frontend', 'editor-menu-and-widget-access' ),
			'admin'    => __( 'Admin only', 'editor-menu-and-widget-access' ),
			'frontend' => __( 'Frontend only', 'editor-menu-and-widget-access' ),
		) as $emwa_key => $emwa_label ) :
			?>
		<option value="<?php echo esc_attr( $emwa_key ); ?>" <?php selected( $profile['toolbar_scope'], $emwa_key ); ?>><?php echo esc_html( $emwa_label ); ?></option>
		<?php endforeach; ?>
	</select></p>
	<p class="description"><?php esc_html_e( 'The list reflects the toolbar available to your administrator account on this screen. Saved frontend-only or role-specific items appear as unavailable here. Appearance link settings apply in both contexts.', 'editor-menu-and-widget-access' ); ?></p>
<?php endif; ?>
<?php
$emwa_unavailable_count = 0;

foreach ( $items as $emwa_id => $emwa_item ) {
	$emwa_saved = 'menus' === $tab ? isset( $profile['menus'][ $emwa_id ] ) : isset( $profile['toolbar'][ $emwa_id ] );

	if ( $emwa_saved && empty( $emwa_item['available'] ) ) {
		++$emwa_unavailable_count;
	}
}
?>
<p class="description">
	<?php
	printf(
		/* translators: %s: localized number of saved rules whose items are not registered on this screen. */
		esc_html( _n( '%s saved rule refers to an unavailable item.', '%s saved rules refer to unavailable items.', $emwa_unavailable_count, 'editor-menu-and-widget-access' ) ),
		esc_html( number_format_i18n( $emwa_unavailable_count ) )
	);
	?>
</p>
<?php if ( 'menus' === $tab ) : ?>
	<div class="emwa-menu-view emwa-view-control">
		<label><input id="emwa-show-all" type="checkbox" name="show_all" value="1" form="emwa-role-picker" <?php checked( $show_all ); ?>> <?php esc_html_e( 'Show all items', 'editor-menu-and-widget-access' ); ?></label>
		<span class="description"><?php esc_html_e( 'Include items already hidden by role permissions and unavailable plugin items.', 'editor-menu-and-widget-access' ); ?></span>
		<noscript><button type="submit" form="emwa-role-picker" class="button"><?php esc_html_e( 'Apply view', 'editor-menu-and-widget-access' ); ?></button></noscript>
	</div>
<?php endif; ?>
<div class="emwa-tools" hidden>
	<label for="emwa-search"><?php esc_html_e( 'Find an item', 'editor-menu-and-widget-access' ); ?></label>
	<input id="emwa-search" type="search" autocomplete="off">
	<button type="button" class="button" data-emwa-bulk="hide"><?php esc_html_e( 'Hide visible items', 'editor-menu-and-widget-access' ); ?></button>
	<button type="button" class="button" data-emwa-bulk="show"><?php esc_html_e( 'Show visible items', 'editor-menu-and-widget-access' ); ?></button>
	<button type="button" class="button" data-emwa-collapse="collapse"><?php esc_html_e( 'Collapse all', 'editor-menu-and-widget-access' ); ?></button>
	<button type="button" class="button" data-emwa-collapse="expand"><?php esc_html_e( 'Expand all', 'editor-menu-and-widget-access' ); ?></button>
	<span class="emwa-search-status" role="status" aria-live="polite" dir="ltr"></span>
</div>
<table class="widefat striped emwa-rules">
	<thead><tr><th scope="col"><?php esc_html_e( 'Hide', 'editor-menu-and-widget-access' ); ?></th><th scope="col"><?php esc_html_e( 'Item', 'editor-menu-and-widget-access' ); ?></th>
	<?php
	if ( 'menus' === $tab ) :
		?>
		<th scope="col"><?php esc_html_e( 'Role visibility', 'editor-menu-and-widget-access' ); ?></th><?php endif; ?><th scope="col"><?php esc_html_e( 'Details', 'editor-menu-and-widget-access' ); ?></th></tr></thead>
	<tbody>
	<?php
	$emwa_status_labels = array(
		'available'         => __( 'Available to this role', 'editor-menu-and-widget-access' ),
		'permission'        => __( 'Already hidden by role permissions', 'editor-menu-and-widget-access' ),
		'parent_permission' => __( 'Already hidden with its parent menu', 'editor-menu-and-widget-access' ),
		'rule'              => __( 'Hidden by Editor Access', 'editor-menu-and-widget-access' ),
		'parent_rule'       => __( 'Parent hidden by Editor Access', 'editor-menu-and-widget-access' ),
		'inactive_rule'     => __( 'Saved hide rule; currently not applied', 'editor-menu-and-widget-access' ),
		'unavailable'       => __( 'Unavailable item; saved rule retained', 'editor-menu-and-widget-access' ),
		'unknown'           => __( 'Availability unknown', 'editor-menu-and-widget-access' ),
	);

	foreach ( $items as $emwa_id => $emwa_item ) :
		$emwa_is_menu        = 'menus' === $tab;
		$emwa_is_hidden      = $emwa_is_menu ? RolePolicy::profile_hides_menu( $profile, $emwa_item['parent'], $emwa_item['slug'] ) : ! empty( $profile['toolbar'][ $emwa_id ] );
		$emwa_managed        = ! empty( $emwa_item['managed'] );
		$emwa_input_id       = 'emwa-item-' . hash( 'sha256', $emwa_id );
		$emwa_role_available = ! $emwa_is_menu || ! empty( $emwa_item['role_available'] );
		?>
		<tr data-emwa-role-available="<?php echo $emwa_role_available ? '1' : '0'; ?>" data-emwa-group="<?php echo esc_attr( $emwa_item['group'] ); ?>" data-emwa-depth="<?php echo esc_attr( $emwa_item['depth'] ); ?>" data-emwa-search="<?php echo esc_attr( $emwa_item['label'] . ' ' . ( $emwa_item['slug'] ?? $emwa_id ) ); ?>" <?php echo ! $emwa_role_available && ! $show_all ? 'hidden' : ''; ?>>
			<td class="emwa-check"><input type="checkbox" id="<?php echo esc_attr( $emwa_input_id ); ?>" name="hidden[]" value="<?php echo esc_attr( $emwa_id ); ?>" <?php checked( $emwa_is_hidden ); ?> <?php disabled( $emwa_managed ); ?>></td>
			<td class="emwa-depth-<?php echo esc_attr( min( $emwa_item['depth'], 3 ) ); ?>">
				<label for="<?php echo esc_attr( $emwa_input_id ); ?>"><?php echo esc_html( $emwa_item['label'] ); ?></label>
				<?php if ( 0 === $emwa_item['depth'] ) : ?>
					<button type="button" class="button-link emwa-group-toggle" data-emwa-toggle="<?php echo esc_attr( $emwa_item['group'] ); ?>" aria-expanded="true" hidden><?php esc_html_e( 'Collapse', 'editor-menu-and-widget-access' ); ?></button>
				<?php endif; ?>
			</td>
			<?php if ( $emwa_is_menu ) : ?>
				<td class="emwa-role-visibility">
					<?php
					foreach ( $emwa_item['role_status'] as $emwa_status ) :
						?>
						<span class="emwa-status emwa-status-<?php echo esc_attr( $emwa_status ); ?>"><?php echo esc_html( $emwa_status_labels[ $emwa_status ] ); ?></span>
					<?php endforeach; ?>
				</td>
			<?php endif; ?>
			<td>
				<?php if ( $emwa_managed ) : ?>
					<?php esc_html_e( 'Use Appearance menu links above', 'editor-menu-and-widget-access' ); ?>
				<?php elseif ( ! empty( $emwa_item['legacy'] ) ) : ?>
					<?php esc_html_e( 'Legacy rule: applies wherever this slug appears', 'editor-menu-and-widget-access' ); ?>
				<?php elseif ( empty( $emwa_item['available'] ) ) : ?>
					<span class="emwa-unavailable"><?php esc_html_e( 'Unavailable here; saved rule retained', 'editor-menu-and-widget-access' ); ?></span>
				<?php elseif ( $emwa_is_menu && ! empty( $emwa_item['capability'] ) ) : ?>
					<code dir="ltr"><?php echo esc_html( $emwa_item['capability'] ); ?></code>
				<?php endif; ?>
				<?php if ( $emwa_is_menu ) : ?>
					<div class="description emwa-route" dir="ltr"><?php echo esc_html( $emwa_item['slug'] ); ?></div>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	<?php
	if ( ! $items ) :
		?>
		<tr><td colspan="<?php echo 'menus' === $tab ? '4' : '3'; ?>"><?php esc_html_e( 'No items are available on this screen. Existing rules are preserved.', 'editor-menu-and-widget-access' ); ?></td></tr><?php endif; ?>
	</tbody>
</table>
<p class="description"><?php esc_html_e( 'Hiding a parent also hides its links to child items. Child rules stay saved independently.', 'editor-menu-and-widget-access' ); ?></p>
