# Findings reference

Plugin Health Guard findings are diagnostic. Back up the shop and confirm the named plugin and version before changing files.

## Critical findings

### Active version missing

Plugin Manager records an active version whose directory is absent. Restore the exact directory or reinstall that plugin version before using its features.

### Manifest missing or version mismatch

The package cannot be identified reliably, or the directory and declared version disagree. Replace it with a complete release from the plugin's trusted source.

### Symbolic link found

A package entry redirects to another filesystem location. Confirm that the link is intentional and that its target cannot escape the expected plugin boundary.

### World-writable PHP file

Any server user can modify the PHP file. Ask the host or server administrator to correct ownership and permissions immediately.

## Review findings

### Multiple versions retained

More than one version directory exists for a plugin. Keep a rollback version only until the replacement is tested, then remove obsolete directories. The scanner checks every retained version, not only the active one.

### PHP direct-access guard not detected

An internal catalog PHP file does not contain a recognized `IS_ADMIN_FLAG` guard and does not explicitly bootstrap Zen Cart through `includes/application_top.php`. Review the file's purpose before changing it. A public endpoint can be intentional, while an internal class, observer, data file, function file, or page loader usually needs the standard guard.

### Sensitive file type in package

A package contains a log, SQL file, backup, environment file, or similar item that may be unnecessary or exposed. Confirm its purpose and remove it from production when it is not required.

### OPcache unavailable, low, or restarting

The website PHP process cannot report an active OPcache, has little free OPcache memory, or reports memory or hash restarts. Ask the host or server administrator to review the PHP pool. Command-line PHP can use a different cache from the website.

## Information and Good findings

Information findings provide context and do not necessarily require a change. Good findings confirm a protective condition, such as an active OPcache.

## Rerunning the report

Each page load creates a new report. Findings are not saved. A repeated path means the matching file or retained version directory is still present.
