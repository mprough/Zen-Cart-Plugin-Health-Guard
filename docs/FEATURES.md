# Feature list

## Plugin inventory

- Inventories every plugin key and version directory under `zc_plugins`.
- Compares directories with the active versions recorded by Plugin Manager.
- Counts files, PHP files, static assets, and package size.
- Identifies missing active versions and retained rollback versions.

## Package safety checks

- Checks for missing, unreadable, and version-mismatched manifests.
- Reports symbolic links and world-writable directories or PHP files.
- Flags potentially sensitive file types such as logs, SQL files, backups, and environment files.
- Reviews internal catalog PHP for direct-access protection.
- Recognizes standard `IS_ADMIN_FLAG` guards and intentional endpoints that load Zen Cart through `includes/application_top.php`.

## PHP runtime checks

- Reports the PHP and Zen Cart versions visible to the admin request.
- Reports the PHP memory limit and realpath-cache configuration.
- Reports whether OPcache is active and whether timestamp validation is enabled.
- Warns when OPcache memory is low or runtime statistics show memory or hash restarts.
- Omits the cached-script list to avoid exposing unrelated absolute filenames.

## Reporting and safety

- Displays Critical, Review, Information, and Good findings in Zen Cart admin.
- Exports the current report as JSON for support review.
- Generates reports in memory without storing scan history.
- Performs no storefront work and makes no automatic file, permission, cache, PHP, database, or server-configuration changes during a scan.
