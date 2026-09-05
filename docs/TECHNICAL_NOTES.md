# Technical notes

## Request boundary

Plugin Health Guard registers no storefront observer, autoloader, page module, AJAX endpoint, scheduled task, or output filter. Its scanner class is loaded only after an authorized administrator opens the Plugin Health Guard admin page.

## What the scan measures

The filesystem inventory reads directory entries and file metadata under the shop's zc_plugins directory. It reads each manifest and up to the first 4 KB of relevant catalog PHP files. It does not execute third-party manifests or plugin files.

The Plugin Manager inventory reads TABLE_PLUGIN_CONTROL when that table constant is available. Only rows with active status are used to identify an installed version. No scan query writes to the database.

OPcache diagnostics use opcache_get_status(false) and opcache_get_configuration(). Passing false prevents collection of the full cached-script list, which keeps the report smaller and avoids disclosing unrelated absolute filenames. Some hosts disable these functions, and the report treats that as unavailable rather than an application failure.

## Finding levels

- Critical: a missing active version, missing manifest, symbolic link, or world-writable PHP file.
- Review: a condition that deserves manual evaluation, such as retained versions, sensitive extensions, manifest inconsistencies, or low OPcache memory.
- Information: useful context that is not itself a failure.
- Good: a confirmed protective runtime condition.

The direct-access-guard check is intentionally conservative. It checks relevant catalog PHP source for an IS_ADMIN_FLAG guard but does not claim that the file is externally reachable. Web-server configuration, rewrite rules, and parent-directory protection still determine reachability.

## Data handling

Reports are generated in memory and are not stored. JSON exports can include plugin keys, versions, relative filenames, PHP configuration values, and runtime statistics. Shop owners should review an export before sharing it publicly.

## Performance

Runtime is proportional to the number of plugin directories and files. Because the scan is admin initiated and does not run during storefront bootstrap, it cannot slow product, cart, checkout, feed, callback, or AJAX requests.
