#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
version_root="$root/files/zc_plugins/PluginHealthGuard/v1.0.3"

test -f "$version_root/manifest.php"
test -f "$version_root/Installer/ScriptedInstaller.php"
test -f "$version_root/admin/plugin_health_guard.php"
test -f "$version_root/admin/includes/functions/extra_functions/plugin_health_guard_menu.php"
test -f "$root/docs/FEATURES.md"
test -f "$root/docs/FINDINGS_REFERENCE.md"
test -f "$root/NOTICE.md"

find "$root/files" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php -d zend.assertions=1 -d assert.exception=1 "$root/tests/scanner-test.php"

grep -Fq "'pluginVersion' => 'v1.0.3'" "$version_root/manifest.php"
grep -Fq "public string \$version = '1.0.3'" "$version_root/Installer/ScriptedInstaller.php"
grep -Fq "'PLUGIN_HEALTH_GUARD_VERSION', '1.0.3'" "$version_root/Installer/ScriptedInstaller.php"
grep -Fq "zen_page_key_exists('pluginHealthGuard')" "$version_root/Installer/ScriptedInstaller.php"
grep -Fq "zen_page_key_exists('pluginHealthGuard')" "$version_root/admin/includes/functions/extra_functions/plugin_health_guard_menu.php"
grep -Fq "FILENAME_PLUGIN_HEALTH_GUARD" "$version_root/admin/includes/extra_datafiles/plugin_health_guard.php"
if grep -Eq '^\$installedPlugins[[:space:]]*=' "$version_root/admin/plugin_health_guard.php"; then
    echo 'Admin page must not overwrite Zen Cart bootstrap variable $installedPlugins.' >&2
    exit 1
fi

echo "Package checks passed"
