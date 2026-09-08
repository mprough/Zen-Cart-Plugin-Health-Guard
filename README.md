# Zen Cart Plugin Health Guard

Zen Cart Plugin Health Guard gives shop owners a read-only health report for encapsulated plugins and the PHP environment that runs them. It inventories Plugin Manager packages, identifies retained versions and risky files, checks permissions and symbolic links, and reports OPcache and realpath-cache settings without adding work to storefront requests.

The current production release is version 1.0.3.

## Features

- Runs only from **Tools > Plugin Health Guard** in the protected Zen Cart admin.
- Inventories every plugin key and version under `zc_plugins`.
- Shows the version and status recorded by Zen Cart's Plugin Manager.
- Warns about retained plugin versions, malformed or missing manifests, symbolic links, writable PHP files, sensitive file types, and internal PHP files without a direct-access guard.
- Recognizes intentional catalog endpoints that load Zen Cart's `includes/application_top.php` before performing work.
- Reports PHP, Zen Cart, OPcache, memory, and realpath-cache information.
- Exports the current report as JSON for support.
- Makes no storefront observer registrations, database changes during scans, automatic deletions, cache changes, or web-server configuration changes.

See the [complete feature list](docs/FEATURES.md) and [findings reference](docs/FINDINGS_REFERENCE.md) for details.

## Compatibility

- Zen Cart 2.0.x, 2.1.x, and 2.2.x
- PHP 8.0 through 8.5, within the PHP versions supported by the installed Zen Cart release

## Installation

1. Back up the shop files and database.
2. From the production ZIP, copy the `zc_plugins` directory to the shop root. From a repository checkout, copy the contents of `files/` instead. This adds files only under `zc_plugins/PluginHealthGuard`.
3. In the Zen Cart admin, open **Modules > Plugin Manager**.
4. Install **Zen Cart Plugin Health Guard**.
5. Open **Tools > Plugin Health Guard**.

When upgrading, copy the new files first, then upgrade the plugin in Plugin Manager. Existing configuration and admin-page permissions are preserved.

No core or template files are overwritten.

## Using the report

Run the report after installing, removing, or upgrading plugins. A warning is a prompt for review, not proof that a plugin is broken. Do not remove an older version directory until the replacement has been tested and rollback is no longer required.

Every run creates a fresh report. Results are not stored or carried forward. The scanner examines every version directory still present under `zc_plugins`, including inactive rollback versions, so a finding for an older version remains until that directory is corrected or removed.

Plugin Health Guard deliberately does not apply blanket caching or throttling to `zc_plugins`. Those controls must be matched to the public route and its behavior so carts, checkout, AJAX, feeds, callbacks, and scheduled jobs are not damaged.

## Uninstall

Use **Modules > Plugin Manager** to uninstall the plugin. Uninstall removes only its admin-page registration and installed-version marker. Delete `zc_plugins/PluginHealthGuard` afterward if the files are no longer wanted.

## Database changes

The installer adds one configuration row named `PLUGIN_HEALTH_GUARD_VERSION` and one Tools-menu registration. It stores no scan results or shop data.

## Limitations

- The report examines files and current PHP settings. It does not benchmark individual observer callbacks or prove that a URL is externally reachable through a CDN or web server.
- The direct-access-guard check recognizes standard `IS_ADMIN_FLAG` guards and catalog endpoints that explicitly load `includes/application_top.php`. Review other warnings in context before changing a file.
- OPcache information can be restricted by the host and may be unavailable.
- The plugin never edits server rules, PHP settings, plugin files, or active-version records.

## Support and security

Report bugs or security concerns through the [PRO-Webs helpdesk](https://prowebsinc.zohodesk.com/portal/en/newticket). Installation, configuration, customization, server administration, and interpretation for third-party plugins are not included.

For reporting guidance, see [SECURITY.md](SECURITY.md).

## License and warranty

Copyright 2026 Melanie Prough, PRO-Webs, Inc. Released under GPL-2.0-only. Free distribution is provided without warranty. See [LICENSE](LICENSE) and [NOTICE.md](NOTICE.md).

- [Repository](https://github.com/mprough/Zen-Cart-Plugin-Health-Guard)
- [PRO-Webs.net](https://pro-webs.net/)
