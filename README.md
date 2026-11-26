# Editor Menu and Widget Access

Version 4 requires WordPress 6.2+ and PHP 7.4+. WooCommerce is optional. Configure the plugin under **Settings → Editor Access**.

## What it does

Select built-in or custom roles, then set Appearance permissions and hide admin menu or toolbar links for each role. You can preview an existing user's likely access, copy a role profile, and retain rules for menu items supplied by temporarily inactive plugins. Administrators, super admins and users with `manage_options` are exempt.

The Menus / Navigation, Identity, Styles, Patterns and Templates visibility settings also hide their links in the Site Editor sidebar. Hiding a link does **not** block its URL. The separate Customizer restriction blocks access to the core WordPress Customizer. WordPress shares `edit_theme_options` across Widgets, Menus, the Site Editor and some theme settings, so an Appearance grant cannot be limited to Widgets alone. Other plugins may add or deny permissions independently.

On a genuine first installation, Editor and Shop Manager are selected automatically, with the runtime Appearance grant enabled. Menus/Navigation and Widgets remain visible where supported by the theme. Themes, Customize, Identity, Styles, Patterns, Templates and Fonts are hidden. A block theme retains the Site Editor entry so Navigation remains reachable; a classic theme hides the additional Design/Site Editor entry. Untouched defaults follow later theme changes; explicitly saved Site Editor choices take precedence. Ordinary Pages access is preserved. These are visibility defaults, not granular permission restrictions. Upgrades and reactivation retain saved profiles, including an explicitly empty role selection. Missing Shop Manager roles are supported, so installing WooCommerce later uses the saved default profile.

The Admin Menu list defaults to items available to the edited role, including links hidden by Editor Access so they remain easy to unhide. **Show all items** also reveals permission-filtered and unavailable items. The Role visibility column distinguishes role permissions, parent-menu hiding, active or inactive plugin rules and unknown availability. The estimate uses the role's stored permissions, WordPress capability mappings and the configured runtime Appearance grant; it does not borrow an existing account's extra roles or individual permissions. Conditional plugin menus can differ. Search and bulk actions work on the displayed list; filtering never deletes saved rules. The toggle also works without JavaScript using Apply view.

Settings changes create backups; the ten most recent are available under **Help & Backups**. Export and import are available there too. Imports merge selected roles and replace matching profiles, retaining other profiles.

## Upgrading from version 3

Version 4 copies legacy settings into `emwa_config` and keeps the original options in place, with a separate `emwa_legacy_backup`. An empty legacy role list still selects Editor and Shop Manager, as it did in version 3. You can deliberately clear all selected roles in version 4. Saves preserve settings for unavailable menu items and unedited roles or tabs; stale or incomplete forms are rejected.

Every activation backs up and removes stored positive grants for `edit_theme_options`, `edit_themes`, `update_themes`, `delete_themes`, `install_themes` and `upload_themes` from Editor, Author, Contributor and Subscriber, restoring their WordPress defaults for this capability set. This also runs once after an upgrade, since WordPress updates do not fire activation hooks. Existing profiles are retained and Appearance access is granted at runtime, so deactivation ends the plugin's grants without writing role capabilities.

Version 3 could also remove theme-management grants from any selected role. If the preserved legacy role list includes Administrator, activation restores missing `edit_themes`, `update_themes`, `delete_themes` and `install_themes` grants. Explicit denials, unrelated capabilities, individual-user permissions and custom roles are preserved. Custom roles have no WordPress-defined defaults, so historical removals there require review rather than guessed replacement. WooCommerce's Shop Manager has `edit_theme_options` by default and keeps it, including after deactivation.

Each changed role is fully backed up under **Help & Backups** before any role is modified. A backup failure prevents cleanup and blocks explicit activation; upgrade failures display an administrator notice and remain eligible for retry. The ten most recent role snapshots are retained. Cleanup cannot distinguish grants made by this plugin from grants made by another role editor; explicit activation resets the specified positive grants regardless of origin. Atomic role writes reject concurrent permission changes. Another permissions plugin can change capabilities again after cleanup; this plugin does not continuously enforce stored role defaults.

Migrated Customize rules also hide the Site Editor and Patterns, preserving version 3 visibility. Before downgrading, export settings and back up the database: version 4 changes are not written back to the legacy options. Ordinary uninstall retains data and role capabilities. To purge plugin data on uninstall, define `EMWA_DELETE_DATA` as `true` in `wp-config.php` before uninstalling.

## Development

Composer and npm are used for checks only; the plugin has no runtime dependency on either. Run these checks before preparing a release:

```sh
composer install
composer lint
composer compatibility
node --check assets/admin/settings.js
python3 bin/build-release.py
```

Integration tests **change their WordPress database**. Run them only on a disposable site, as in [the CI workflow](.github/workflows/compatibility.yml):

```sh
EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/integration.php --skip-plugins --skip-themes
EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/role-permissions.php --skip-plugins --skip-themes
EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/profile-preview.php --skip-plugins --skip-themes
EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/defaults.php --skip-plugins --skip-themes
EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/menu-availability.php --skip-plugins --skip-themes
EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/audit.php --skip-plugins --skip-themes
EMWA_TEST_SITE=1 bash tests/activation-lifecycle.sh /path/to/disposable-wordpress
```

The [25 November lifecycle and compatibility audit](docs/audit-2026-11-25.md) records confirmed defects, fixes, tested scenarios and remaining integration risks.

For WooCommerce and browser test setup, see the [CI workflow](.github/workflows/compatibility.yml). The [compatibility record](docs/compatibility.md) distinguishes completed checks from planned ones; the [support audit](docs/support-audit.md) records reports that still need theme-specific verification.

## Translations and release package

User-facing strings use the `editor-menu-and-widget-access` text domain. WordPress language packs take priority over the bundled Italian catalogues. User language preferences apply to the admin interface. Unicode search uses the current document language, displayed counts use locale formatting, and backup dates follow the site date/time preferences and timezone. The release ZIP includes `messages.pot` for translators. After changing English strings, regenerate the POT, update both PO files, and compile the MO files:

```sh
python3 bin/make-pot.py
wp i18n update-po messages.pot lang/it.po
wp i18n update-po messages.pot lang/it_IT.po
msgfmt --check --check-format -o lang/editor-menu-and-widget-access-it.mo lang/it.po
msgfmt --check --check-format -o lang/editor-menu-and-widget-access-it_IT.mo lang/it_IT.po
```

The generated PO files should omit the Plugin Name, Plugin URI, Author and Author URI metadata entries before compilation. The POT helper writes a conventional gettext timestamp accepted by translation editors.

On a disposable site with the Italian core language installed, run `EMWA_TEST_SITE=1 wp --path=/path/to/disposable-wordpress eval-file tests/localisation.php --skip-plugins --skip-themes`. The localisation browser suite also needs the Arabic/Turkish core language packs and `tests/fixtures/localisation.php`; setup is included in the CI workflow.

`python3 bin/build-release.py` builds a ZIP containing runtime files and translation resources in `build/`. It does not publish the plugin.
