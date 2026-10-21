#!/usr/bin/env python3
"""Build one version of the Zen Cart repository distribution."""
from pathlib import Path
import re
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
KEY = 'PluginHealthGuard'
VERSION = '1.0.4'
PLUGIN_ID = 2457
PLUGIN = ROOT / 'files' / 'zc_plugins' / KEY / ('v' + VERSION)
manifest = (PLUGIN / 'manifest.php').read_text()
ids = re.findall(r"'pluginId'\s*=>\s*(\d+)\s*,", manifest)
versions = re.findall(r"'pluginVersion'\s*=>\s*'([^']+)'", manifest)
if ids != [str(PLUGIN_ID)] or versions != ['v' + VERSION]:
    raise SystemExit('Manifest ID or version mismatch')
installer = (PLUGIN / 'Installer' / 'ScriptedInstaller.php').read_text()
if "public string $version = '" + VERSION + "'" not in installer:
    raise SystemExit('Installer version mismatch')

target = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / 'dist' / (KEY + '-v' + VERSION + '-zen-repository.zip')
target.parent.mkdir(parents=True, exist_ok=True)
paths = list(PLUGIN.rglob('*')) + list((ROOT / 'docs').rglob('*'))
paths += [ROOT / n for n in ['README.md', 'CHANGELOG.md', 'LICENSE', 'NOTICE.md', 'SECURITY.md']]
with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as archive:
    for path in sorted(set(paths)):
        if path.is_file():
            archive.write(path, path.relative_to(ROOT))
with zipfile.ZipFile(target) as archive:
    names = archive.namelist()
    manifests = [name for name in names if name.endswith('/manifest.php')]
    if archive.testzip() is not None or manifests != [str((PLUGIN / 'manifest.php').relative_to(ROOT))]:
        raise SystemExit('Archive integrity or manifest count failed')
    if any(name.startswith('files/') and not name.startswith(str(PLUGIN.relative_to(ROOT)) + '/') for name in names):
        raise SystemExit('Archive contains an unexpected installation path')
    if any(name.endswith('.zip') or '/.git/' in name for name in names):
        raise SystemExit('Archive contains a nested archive or Git metadata')
print(str(target))
