<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

function emwaAdminbar_section_cb($args) {
	$roleString = get_option('emwa_role_string');

	echo '<p>' . __('Select the Adminbar items to <strong>hide</strong> from the <strong>' . $roleString . '</strong> role(s):', 'editor-menu-and-widget-access') . '</p>';
	echo '<p><i>' . __('Please note: Some of these adminbar items are already hidden from the ' . $roleString . ' role(s), but you can select them again here to make sure.', 'editor-menu-and-widget-access') . '</i></p>';
}

function emwa_get_adminbar_nodes($wp_admin_bar) {

	$barNodes = $wp_admin_bar -> get_nodes();
	$adminbarArray = [];

	if (is_array($barNodes) || is_object($barNodes)) {
		foreach ($barNodes as $barNode) {
			$id = $barNode -> id;
			$title = $barNode -> title;
			$parent = $barNode -> parent;
			$adminbarArray[] = array('id' => $id, 'title' => $title, 'parent' => $parent);
		}
	}

	$GLOBALS['adminbarArray'] = $adminbarArray;
}
add_action('admin_bar_menu', 'emwa_get_adminbar_nodes', 999);

// Build the multidimensional array tree
function arrayTree(array $items, $parentId = '') {
	$branch = array();

	foreach ($items as $item) {
		if ($item['parent'] == $parentId) {
			$children = arrayTree($items, $item['id']);
			if ($children) {
				$item['children'] = $children;
			}
			$branch[] = $item;
		}
	}

	return $branch;
}

function renderList(array $barItems) {
	$options = get_option('emwaAdminbar');
	echo '<ol class="emwaGroupAdminbar">';

	foreach ($barItems as $barItem) {

		$itemTitle = $barItem['title'];
		$itemTitle = preg_replace('#(<span.*?>).*?(</span>)#', '', $itemTitle);
		$itemTitle = preg_replace('#(<img.*?>)#', '', $itemTitle);
		$id = $barItem['id'];

		if ($itemTitle !== '' && (!is_numeric($itemTitle))) {
			$itemTitle = $itemTitle;
		} else {
			$itemTitle = ucwords(str_replace(array('-', '_'), array(' ', ' '), $id));
		}

		if ($barItem['parent'] == '') {
			$fontWeight = 'font-weight: bold;';
		} else {
			$fontWeight = '';
		}
?>

		<li>
			<input type="checkbox" name="emwaAdminbar[<?php echo $id; ?>]" value="1" <?php checked(isset($options[$id])); ?> />
			<span style="<?php echo $fontWeight; ?>"><?php echo $itemTitle; ?></span>

	<?php
		foreach ($barItem as $key => $value) {
			if (is_array($value)) {
				echo renderList($value);
			}
		}

		echo '</li>';
	}
	echo '</ol>';
}

// Adminbar callback
function emwa_adminbar_menus_cb($args) {
	$adminbarArray = $GLOBALS['adminbarArray'];
	$barItems = arrayTree($adminbarArray);
	$barCheckboxes = renderList($barItems);

	echo $barCheckboxes;
	//echo '<pre>'; print_r( $barItems ); echo '</pre>'; // Testing
}
