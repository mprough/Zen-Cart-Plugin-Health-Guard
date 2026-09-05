#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
version_root="$root/files/zc_plugins/PluginHealthGuard/v1.0.0"

test -f "$version_root/manifest.php"
test -f "$version_root/Installer/ScriptedInstaller.php"
test -f "$version_root/admin/plugin_health_guard.php"
test -f "$version_root/admin/includes/functions/extra_functions/plugin_health_guard_menu.php"

find "$root/files" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php -d zend.assertions=1 -d assert.exception=1 "$root/tests/scanner-test.php"

rg -q "'pluginVersion' => 'v1.0.0'" "$version_root/manifest.php"
rg -q "public string \\$version = '1.0.0'" "$version_root/Installer/ScriptedInstaller.php"
rg -q "'PLUGIN_HEALTH_GUARD_VERSION', '1.0.0'" "$version_root/Installer/ScriptedInstaller.php"

echo "Package checks passed"
