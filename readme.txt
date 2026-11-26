=== Editor Menu and Widget Access ===
Contributors: GuyPrimavera
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YVPWSJB4SPN5N
Tags: widgets, menus, editor, admin, woocommerce
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 4.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Give selected roles appearance access and simplify their WordPress admin menus and toolbar.

== Description ==

Editor Menu and Widget Access is an open-source plugin that helps site owners let Editors, WooCommerce Shop Managers, or selected custom roles manage appearance-related content. Configure each role under Settings > Editor Access.

* Grant the shared WordPress appearance capability while respecting explicit capability denials from other plugins.
* Hide specific Appearance links, plugin menu links, submenu links, and toolbar items per role.
* Hide Navigation, Identity, Styles, Patterns and Templates sidebar links inside the Site Editor.
* Show items available to the edited role by default, with a Show all items toggle and clear role-visibility reasons.
* Find menu items quickly, expand or collapse groups, and see how many saved rules refer to unavailable items.
* Preview saved Appearance access and estimated top-level menus for an existing user with the selected role.
* Review and copy a complete saved role profile to another role without changing which roles are selected or their stored WordPress capabilities.
* Save rules for unavailable plugin items so they return when those plugins are reactivated.
* Export/import profiles, restore one of ten recent backups, and reset one role and section at a time.
* Optionally deny core WordPress Customizer access for selected role(s).
* Reset a built-in role’s Appearance permission to its WordPress default without changing unrelated capabilities.
* Work with WooCommerce legacy order storage and High-Performance Order Storage (HPOS).

Menu and toolbar checkboxes hide links; they do not block direct URLs, AJAX or REST requests. WordPress uses the same edit_theme_options capability for several appearance features, so granting it may also allow theme or third-party screens that use that capability. Review each role's permissions after changing grants.

== Installation ==

1. Install the plugin through Plugins > Add New, or upload the editor-menu-and-widget-access folder to wp-content/plugins.
2. Activate it.
3. Open Settings > Editor Access to select roles and configure their access.

On a first installation, Editor and Shop Manager receive Appearance access with Menus/Navigation and Widgets shown where the theme supports them. Other additional Appearance tools are hidden. Block themes retain the Site Editor entry for Navigation; normal Pages access is preserved. These are visibility defaults, not separate permissions. WooCommerce is optional. Upgrades and reactivation retain existing settings.

== Frequently Asked Questions ==

= Where are the settings? =

Under Settings > Editor Access. Choose active roles and edit their permissions on Roles & Access; hide Appearance and other admin links on Admin Menu. Historical Appearance settings bookmarks still open.

= Does hiding a menu block direct access? =

No. It changes navigation visibility only. Use WordPress or the relevant plugin's capability controls to block direct access. The optional Customizer restriction is enforced separately.

= What happens to rules when I deactivate another plugin? =

Saved rules remain. They appear as unavailable until that plugin adds the menu or toolbar item again.

= Can I use custom roles? =

Yes. Select a role under Roles & Access, then configure its profile. A newly selected custom role receives no broad appearance grant unless you enable it.

= Can I hide Appearance but keep Menus visible? =

WordPress nests Menus under Appearance. Keep the Appearance parent visible and hide unwanted child links. Hiding the parent also hides its children; it does not revoke direct access.

= Can this plugin show another plugin's menu to an Editor? =

No. It hides links already available to that role. The other plugin controls the capability required to open its screens. Do not grant manage_options solely to reveal a menu.

= How do I hide a theme Customizer link or the frontend Edit link? =

On Admin Menu, hide Customize and optionally deny direct Customizer access on Roles & Access. Theme-added toolbar links to this site's Customizer follow that setting. On Toolbar, select Edit content (frontend toolbar) to hide the page/post Edit link even when it is absent from the administrator's settings screen.

= Why does Editor still see Appearance after deactivation? =

Activation backs up and resets the six theme capabilities historically touched by this plugin on built-in content roles. Version 4 grants Appearance access at runtime, so its grant ends on deactivation. Another role plugin or a permission assigned directly to a user can still provide access. WooCommerce grants Shop Manager edit_theme_options by default; that permission is preserved.

== Screenshots ==

1. A simplified Appearance menu showing only Widgets and Menus for a selected role.
2. The Roles & Access screen, where you select roles and configure Appearance access and the optional Customizer restriction.
3. The Admin Menu list, with controls to hide links and status labels showing available items, hidden items and items hidden by a parent menu.

== Upgrade safety ==

Version 4 backs up the original options before migration and leaves them unchanged. It keeps ten recent complete configurations and preserves unavailable-item rules. An empty version 3 role option meant Editor and Shop Manager; migration keeps those defaults. In version 4, explicitly clearing all roles disables profiles.

Every activation removes stored positive edit_theme_options, edit_themes, update_themes, delete_themes, install_themes and upload_themes grants from Editor, Author, Contributor and Subscriber. This cleanup also runs once after an upgrade. If the legacy role list selected Administrator, missing core theme-management grants are restored. Explicit denials, unrelated capabilities and custom-role permissions are preserved; custom roles have no WordPress-defined defaults. WooCommerce-owned Shop Manager permissions are retained.

Complete snapshots of changed roles are saved before cleanup. A backup failure blocks explicit activation; failed upgrade cleanup shows an administrator notice and is retried. Existing settings are retained. Deactivation ends runtime grants and restrictions without rewriting stored role permissions. Other permission tools may independently grant access.

Export settings and back up the database before downgrading. Older versions read the preserved pre-upgrade options, not edits made in version 4. Ordinary uninstall retains data.

== Changelog ==

= 4.0.0 =
* Namespaced refactor with stable menu identities and multi-role handling.
* Native admin UI with per-role settings, search and grouped controls.
* Role-filtered menu lists, Show all items, visibility status labels and a no-JavaScript view toggle; filtered rules survive settings saves.
* Navigation, Identity, Styles, Patterns and Templates sidebar links follow their per-role visibility settings.
* First installations show Menus/Navigation and Widgets for Editor and Shop Manager while hiding other additional Appearance tools; upgrades retain saved profiles.
* Legacy settings migration retains original options and backs up changed roles before restoring theme-capability defaults.
* Backups, validated import/export, stale-form and truncated-form protection.
* Runtime appearance grants respect existing explicit capability denials.
* Optional core Customizer restriction; menu hiding remains visibility-only.
* WooCommerce HPOS declaration and order-menu route compatibility.
* Per-role Appearance permission reset with before/after verification and a stored role-capability backup.
* Reviewed profile copying, read-only access preview and unavailable-rule counts.
* Refreshed complete Italian translations using WordPress language-pack conventions.
* Activation resets historical theme grants on built-in content roles; versioned upgrade cleanup also covers updates without reactivation.
* Theme-added Customizer toolbar links follow Customize visibility; the frontend Edit toolbar link can be selected from settings.
* Hardened migration failures, namespace collisions, concurrent role writes, network isolation, late menus, custom role keys and malformed settings input.
* Locale-aware Unicode search, correctly formatted counts and backup dates, improved right-to-left presentation, and an included translation template.
* Requires WordPress 6.2 and PHP 7.4 or newer.

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

= 4.0.0 =
Requires WP 6.2/PHP 7.4. Activation backs up and resets historical theme capabilities on built-in content roles; upgrade cleanup runs once too. Deactivation ends temporary grants. WooCommerce/custom roles retain their permissions. Back up and read Upgrade safety.

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
