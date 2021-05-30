<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

// Callback
function emwaOtherMenus_section_cb($args) {
	$roleString = get_option('emwa_role_string');

	echo '<p>' . __('Select the other menus to <strong>hide</strong> from the <strong>' . $roleString . '</strong> role(s):', 'editor-menu-and-widget-access') . '</p>';
	echo '<p><em>' . __('Please note: Some of these menu items are already hidden from the ' . $roleString . ' role(s), but you can select them again here to make sure.', 'editor-menu-and-widget-access') . '</em></p>';
}

// Other Menus callback
function emwa_field_menus_cb($args) {
	$emwaOptions = get_option('emwa_settings');
	$menuItems = $GLOBALS['menu'];
	$menuSubItems = $GLOBALS['submenu'];
	$restrictedCaps = array('manage_options', 'administrator');

	echo '<ol class="emwaGroupParent">';

	foreach ($menuItems as $menuItem) {
		$menuTitle = $menuItem[0];
		$menuCaps = $menuItem[1];
		$menuId = htmlentities($menuItem[2]);
		if ((isset($menuItem[6])) && (substr($menuItem[6], 0, 4) === "dash")) {
			$menuIcon = $menuItem[6];
		} else {
			$menuIcon = 'dashicons-admin-post';
		}

		if ($menuId === 'themes.php') {
			$disabled = 'disabled';
		} else {
			$disabled = '';
		}

		if (($menuTitle) !== "" && (!in_array($menuCaps, $restrictedCaps, true))) {

			echo '
			<li class="emwaListParent dashicons-before ' . $menuIcon . '">'; ?>
			<input type="checkbox" <?php echo $disabled; ?> class="emwaCheckParent " name="emwa_settings[<?php echo $menuId; ?>]" value="1" <?php checked(isset($emwaOptions[$menuId])); ?> />
			<span style="font-weight: bold; text-indent: -5px;" class=""><?php echo $menuTitle; ?></span>
			<ol class="emwaGroupChild">
				<?php
				// Submenus
				foreach ($menuSubItems as $menuSubItem => $menuSubValues) {
					if ($menuSubItem == $menuId) {
						foreach ($menuSubValues as $menuSubValue => $menuSubValueValue) {

							$menuSubTitle = $menuSubValueValue[0];
							$menuSubCaps = $menuSubValueValue[1];
							$menuSubId = htmlentities($menuSubValueValue[2]);

							if ((!in_array($menuSubTitle, array(''), true))
								&& (!in_array($menuSubCaps, $restrictedCaps, true))
								&& (!in_array($menuSubId, array($menuId), true))
								&& (
									($menuId !== 'themes.php') || (!in_array($menuSubId, array('custom-background', 'custom-header', 'widgets.php', 'nav-menus.php'), true)))
								&& (
									($menuId !== 'themes.php') || (substr($menuSubId, 0, 13) !== "customize.php"))
							) {

								echo '
							  <li class="emwaListChild">'; ?>
								<input type="checkbox" name="emwa_settings[<?php echo $menuSubId; ?>]" value="1" <?php checked(isset($emwaOptions[$menuSubValueValue[2]])); ?> />
								<span><?php echo $menuSubTitle; ?></span>
								</li>
	<?php
							}
						}
					}
				}
				echo '</ol>';
			}
			echo '</li>';
		}
		echo '</ol>';
	}
