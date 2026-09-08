# Change history

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
