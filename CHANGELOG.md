# Change history

## 1.0.5, 2026-11-01

- Extended support to Zen Cart 1.5.8 through 3.0.0 and PHP 7.4 through 8.5.
- Replaced PHP 8.0-only syntax and functions that stopped the report from loading on PHP 7.4.
- Fixed the JSON download button, which reloaded the page instead of exporting.
- Fixed a fatal error on non-English admins of Zen Cart 1.5.8 and 2.0.x.
- Stopped false world-writable findings on Windows servers.
- Added installed plugins whose whole folder is gone to the report, and showed disabled plugins as disabled.
- Reported symbolic links at the plugin and version directory levels, not only inside a version.
- Reported manifests with a PHP syntax error. Read double-quoted manifests correctly and ignored comments.
- Recognized spaced `IS_ADMIN_FLAG` guards, guards after long file headers, and endpoints that build the `application_top.php` path. Text inside comments no longer counts as a guard.
- Made the Tools-menu repair work on every release, and let a translated menu label override the English one.
- Removed the absolute server path from the JSON report.
- Added the `IS_ADMIN_FLAG` guard before the admin page's first include, and removed the hard-coded version from its class path.

## 1.0.4, 2026-10-21

- Advance the patch version for resubmission to the migrated Zen Cart plugin repository.
- Retain official plugin ID 2457; the forum release number is not the plugin ID.
- Prepare the distribution ZIP with one current version directory and one readable manifest.
- Check manifest ID and version consistency during release validation.

## 1.0.3, 2026-09-08

- Recognized intentional catalog endpoints that explicitly load Zen Cart's `includes/application_top.php` as bootstrapped entry points.
- Continued reporting unguarded internal catalog PHP files and added regression coverage for both cases.

## 1.0.2, 2026-09-08

- Stopped direct-access guard warnings for catalog language files, including `includes/languages/*/extra_definitions`, because those files contain definitions rather than standalone storefront behavior.
- Added a regression test for an unguarded language-definition file.

## 1.0.1, 2026-09-08

- Fixed an admin fatal error caused by reusing Zen Cart's `$installedPlugins` bootstrap variable for scanner inventory data.
- Preserved an existing Tools-menu registration and its permissions during upgrades.

## 1.0.0, 2026-09-05

- Initial release.
- Added a read-only Plugin Manager package inventory and findings dashboard.
- Added checks for retained versions, manifests, file permissions, symbolic links, sensitive files, and missing PHP direct-access guards.
- Added PHP, Zen Cart, OPcache, and realpath-cache diagnostics.
- Added JSON report export and self-repairing Tools-menu registration.
