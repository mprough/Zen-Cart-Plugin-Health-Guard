# Shop owner guide

## When to run it

Run Plugin Health Guard after a plugin installation, upgrade, removal, server migration, or PHP configuration change. It is also useful before requesting technical support.

## What to do with findings

Start with critical findings. Confirm that the plugin and version named in the report are expected before changing anything. Back up the shop first, and test file or server changes away from the live checkout.

Retained versions are not automatically unsafe. They are useful during a short rollback window, but old versions should not accumulate indefinitely. Uninstalling a version through Plugin Manager and deleting files are separate actions.

The report is rebuilt whenever the page is opened or **Run report again** is selected. Plugin Health Guard does not save earlier findings. If the same path remains in a new report, that file or version directory is still present and still matches the check.

The scanner checks active and inactive version directories. After an upgrade has been tested, remove obsolete version directories from `zc_plugins/PluginKey` if they are no longer needed for rollback. Never remove the active version shown in the Plugin inventory table.

An OPcache warning belongs with the host or server administrator. Do not paste suggested PHP settings into an unfamiliar server configuration without confirming how that server is managed.

## What the tool does not do

Plugin Health Guard does not remove files, alter permissions, clear OPcache, install server rules, throttle URLs, or cache pages. Blanket rules can interrupt checkout, payment callbacks, feeds, AJAX, or scheduled jobs, so those decisions require route-specific review.

## Sharing a report

Use **Download JSON report** when support needs a copy. Review the file before sending it. The report excludes passwords, database credentials, session data, and the OPcache cached-script list, but relative plugin filenames and server settings remain visible.

See the [findings reference](FINDINGS_REFERENCE.md) for the meaning and recommended response for each report item.
