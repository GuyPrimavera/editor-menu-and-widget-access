=== Editor Menu and Widget Access ===
Contributors: GuyPrimavera
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YVPWSJB4SPN5N
Tags: widgets, widget, appearance, menus, menu, navigation, navigation menu, nav menu, admin, editor, editors, shop manager, woocommerce, users, wp-admin, theme options, options, customize, customise, wordpress, plugin
Requires at least: 3.0.1
Tested up to: 5.7
Stable tag: 3.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow and control Editor and Shop Manager access to the menus, widgets and appearance menu, plus other menus and adminbar items.

== Description ==

This open source and lightweight plugin allows users with the role **Editor** or **Shop Manager** to access the **Menus** and **Widgets** areas of the Appearance menu in WordPress' admin area. 

This is a common task that would be useful for clients to manage themselves to give them more control over their website's content.

This plugin also gives access to the **Customize** submenu and other theme options, but these can be hidden through the options page at **Appearance > Editor Access** if you wish.

You can also select other menus to hide from Editors and Shop Managers (e.g. custom theme or plugin options pages), as well as hiding **any elements on the Admin Bar**.

**Features**

* **Allow access** to Customize, Menus and Widgets for Editors and Shop Managers.
* **Theme Options** remain hidden from these user roles.
* **Hide custom options pages** from these users (if your theme/plugin has a custom settings page you wish to hide).
* **Hide any other menus** or submenus. Simply select which menus you wish to hide.
* **Hide admin bar menus and elements**. Choose which elements you wish to hide.

== Installation ==

**Via FTP**

1. Upload editor-menu-and-widget-access to the /wp-content/plugins/ directory.
2. Activate Editor Menu and Widget Access through the 'Plugins' menu in WordPress.
3. That's it! The default settings are applied automatically, and you can hide other pages in the options page at **Appearance > Editor Access** if you wish.

**Via WordPress Admin**

1. Go to **Plugins** > **Add New**.
2. Search for **Editor Menu and Widget Access** and click **Install**.
3. Click **Activate** once installation is complete.
4. That's it! The default settings are applied automatically, and you can hide other pages in the options page at **Appearance > Editor Access** if you wish.

== Frequently Asked Questions ==

= Do I need to configure this plugin or change any settings? =

No. The default settings are applied automatically once the plugin is activated, but you can choose exactly which pages you'd like to hide through the options page (in the "Appearance" menu whilst logged in as an Admin) if you wish.

= Can I hide my theme's custom options page? =

Yes. Go to **Appearance > Editor Access** and select any other menu items that you'd like to hide.

== Screenshots ==

1. The new options page to control exactly what Editors and Shop Managers can access.
2. Hide anything or everything from the admin bar.
3. The new "Help" tab.
4. The Appearance menu visible to the Editor (with "customize" set to "hidden" in this example).
5. The "Widgets" page being modified by an Editor.

== Changelog ==

= 3.1.2 =
* Capabilities now reset on plugin deactivation.

= 3.1.1 =
* Bug fixed for WooCommerce shop_manager role.

= 3.1 =
* Code refactor.
* Fixed some PHP errors.
* Updated to latest WordPress version.
* Updated stable tag.

= 3.0.2 =
* Updated stable tag and WP version.

= 3.0.1 =
* CSS tweak for admin checkboxes.

= 3.0 =
* Added Admin Bar support for removing adminbar nodes/menus.
* Tabbed options page and redesign.
* Now allows hiding of menus with higher capabilities, in case another plugin has modified the permissions for Editors or Shop Managers.
* MVC model implemented into code.
* "Help" tab added to report and resolve issues more efficiently.
* Text-domain updated to match WP repo.
* Fixed localization issues.
* Re-added some Italian language support.

= 2.3.2 =
* Fixed bug in Customizer when not logged-in as an Editor or Shop Manager.
* Fixed bug in Theme Preview for Shoreditch theme.
* Tested with WP 4.8-beta.

= 2.3.1 =
* Fixed bug in options page.

= 2.3 =
* Fixed version of v2.1 (thanks to @howdy_mcgee)

= 2.2 =
* Reverted changes due to issues with WooCommerce (or lack of it).

= 2.1 =
* Huge update - Many parts of the plugin re-written.
* Widget or Menus now hidden from "customizer" page, if selected in plugin options.
* WooCommerce support for the role "Shop Manager".
* Added options to select any other menu items to hide from Editors/Shop Managers.
* Tested with 150 popular WordPress plugins for potential conflicts.
* Tested with 4.7 and 4.8-alpha.

= 2.0 =
* Options page added.
* Ability to hide each individual page from the Appearance menu.
* Customize links removed if "customize" is hidden.
* Many functions re-written.
* Tested with MultiSite.
* Tested with many popular plugins for potential conflicts.
* Tested with WordPress 4.6 Beta.

= 1.1.1 =
* Corrected Readme.txt.

= 1.1 =
* Cleaned code.

= 1.0 =
* Stable release.

= 0.1 =
* Beta release.

== Upgrade Notice ==

= 3.1.2 =
* Capabilities now reset on plugin deactivation.

= 3.1.1 =
* Bug fixed for WooCommerce shop_manager role.

= 3.1 =
Code refactor and bug fixes.

= 3.0.2 =
Readme updates.

= 3.0.1 =
Minor admin area styling fix.

= 3.0 =
You can now remove adminbar menus and elements!

= 2.3.2 =
Fixed bug in Customizer and Theme Preview.

= 2.3.1 =
Fixed bug in options page.

= 2.3 =
This is a fixed version of v2.1.

= 2.2 =
Bug fix and roll-back.

= 2.1 =
Huge update. You can now select any other menu items to hide from Editors and Shop Managers.

= 2.0 =
Large update. Whole plugin re-written.

= 1.1.1 =
Corrected Readme.txt.

= 1.1 =
Cleaned code.

= 1.0 =
This is the first stable release.

= 0.1 =
Beta release.
