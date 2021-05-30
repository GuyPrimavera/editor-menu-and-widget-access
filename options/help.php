<?php if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { exit; }

function emwaHelp_section_cb($args) {

	echo '<ul id="emwaHelp">';

	echo '<li><a href="https://wordpress.org/plugins/editor-menu-and-widget-access/" target="_blank">';
	echo '<h3>' . __('View this plugin on WordPress.org', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('See the features and recent updates.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://wordpress.org/support/plugin/editor-menu-and-widget-access" target="_blank">';
	echo '<h3>' . __('View the support forum', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('On WordPress.org.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://wordpress.org/support/plugin/editor-menu-and-widget-access#new-post" target="_blank">';
	echo '<h3>' . __('Ask a question', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('Something not working? Let me know.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://wordpress.org/plugins/editor-menu-and-widget-access/#developers" target="_blank">';
	echo '<h3>' . __('See the ChangeLog', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('See what\'s changed.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://translate.wordpress.org/projects/wp-plugins/editor-menu-and-widget-access" target="_blank">';
	echo '<h3>' . __('Translate into your language', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('Je voudrais un sandwich.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://github.com/GuyPrimavera/editor-menu-and-widget-access" target="_blank">';
	echo '<h3>' . __('View the source code on GitHub', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('It\'s a hub for gits.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://plugins.trac.wordpress.org/browser/editor-menu-and-widget-access/" target="_blank">';
	echo '<h3>' . __('View the source code on WP Trac', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('Similar to GitHub.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://plugins.trac.wordpress.org/log/editor-menu-and-widget-access/" target="_blank">';
	echo '<h3>' . __('View the development log', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('On WordPress.org.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://wordpress.org/plugins/editor-menu-and-widget-access/advanced/#plugin-download-history-stats" target="_blank">';
	echo '<h3>' . __('Previous versions', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('Download an older version of the plugin.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://guyprimavera.com/projects/wordpress-plugins/editor-menu-and-widget-access/" target="_blank">';
	echo '<h3>' . __('View the plugin\'s web page', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('On GuyPrimavera.com.', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://wordpress.org/support/plugin/editor-menu-and-widget-access/reviews/#new-post" target="_blank">';
	echo '<h3>' . __('Leave a review', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('Let me know what you think!', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YVPWSJB4SPN5N" target="_blank">';
	echo '<h3>' . __('Donate towards this plugin', 'editor-menu-and-widget-access') . '</h3>';
	echo '<h4>' . __('This full-version is free to use, but every little helps!', 'editor-menu-and-widget-access') . '</h4>';
	echo '</a></li>';

	echo '<ul>';
}
