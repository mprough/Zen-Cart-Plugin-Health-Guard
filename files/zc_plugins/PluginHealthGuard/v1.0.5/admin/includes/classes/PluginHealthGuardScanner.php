<?php

declare(strict_types=1);

final class PluginHealthGuardScanner
{
    private const SENSITIVE_EXTENSIONS = ['bak', 'conf', 'env', 'ini', 'log', 'old', 'orig', 'sql', 'sqlite', 'swp'];
    private const SOURCE_READ_BYTES = 32768;
    private string $root;
    private array $installed;

    public function __construct(string $root, array $installed = [])
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        $this->installed = $installed;
    }

    public function scan(): array
    {
        $started = microtime(true);
        $plugins = [];
        $findings = [];

        if (!is_dir($this->root) || !is_readable($this->root)) {
            $findings[] = $this->finding('critical', 'Plugin directory unavailable', 'The zc_plugins directory is missing or unreadable.');
        } else {
            $keys = $this->directories($this->root);
            foreach ($keys as $key) {
                $plugin = $this->scanPlugin($key);
                $plugins[] = $plugin;
                array_push($findings, ...$plugin['findings']);
            }
            foreach (array_diff(array_map('strval', array_keys($this->installed)), $keys) as $key) {
                $plugin = $this->scanPlugin($key);
                $plugins[] = $plugin;
                array_push($findings, ...$plugin['findings']);
            }
        }

        $runtime = $this->runtime();
        array_push($findings, ...$runtime['findings']);
        usort($findings, static function (array $a, array $b): int {
            $weight = ['critical' => 0, 'warning' => 1, 'info' => 2, 'good' => 3];
            return ($weight[$a['severity']] ?? 9) <=> ($weight[$b['severity']] ?? 9);
        });

        $summary = ['critical' => 0, 'warning' => 0, 'info' => 0, 'good' => 0];
        foreach ($findings as $finding) {
            ++$summary[$finding['severity']];
        }

        return [
            'generated_at' => gmdate('c'),
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
            'plugin_root' => 'zc_plugins',
            'summary' => $summary,
            'runtime' => $runtime['values'],
            'plugins' => $plugins,
            'findings' => $findings,
        ];
    }

    private function scanPlugin(string $key): array
    {
        $path = $this->root . DIRECTORY_SEPARATOR . $key;
        $versions = $this->directories($path);
        usort($versions, static fn(string $a, string $b): int => version_compare(ltrim($b, 'vV'), ltrim($a, 'vV')));
        $findings = [];
        $installed = $this->installed[$key] ?? null;
        $disabled = (int)($installed['status'] ?? 0) === 2;

        if (is_link($path)) {
            $findings[] = $this->finding('critical', 'Symbolic link found', $key . ' is a symbolic link.', $key);
        }
        if (count($versions) > 1) {
            $findings[] = $this->finding(
                'warning',
                'Multiple versions retained',
                $key . ' contains ' . count($versions) . ' version directories. Keep rollback versions only as long as needed.',
                $key
            );
        }
        if ($versions === [] && is_dir($path)) {
            $findings[] = $this->finding('warning', 'No version directory', $key . ' contains no version directories.', $key);
        }

        $versionReports = [];
        foreach ($versions as $version) {
            $versionReport = $this->scanVersion($key, $version);
            $versionReports[] = $versionReport;
            array_push($findings, ...$versionReport['findings']);
        }

        if ($installed === null) {
            $findings[] = $this->finding('info', 'Not recorded as installed', $key . ' is present on disk but is not active in Plugin Manager.', $key);
        } elseif (!in_array((string)($installed['version'] ?? ''), $versions, true)
            && !in_array('v' . ltrim((string)($installed['version'] ?? ''), 'vV'), $versions, true)
        ) {
            $findings[] = $this->finding(
                $disabled ? 'warning' : 'critical',
                $disabled ? 'Disabled version missing' : 'Active version missing',
                $key . ' is recorded as version ' . (string)($installed['version'] ?? 'unknown') . ', but that directory was not found.',
                $key
            );
        }

        return [
            'key' => $key,
            'installed_version' => $installed['version'] ?? null,
            'status' => isset($installed['status']) ? (int)$installed['status'] : null,
            'versions' => $versionReports,
            'findings' => $findings,
        ];
    }

    private function scanVersion(string $key, string $version): array
    {
        $base = $this->root . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . $version;
        $manifest = $base . DIRECTORY_SEPARATOR . 'manifest.php';
        $findings = [];
        $fileCount = 0;
        $phpCount = 0;
        $staticCount = 0;
        $bytes = 0;

        if (is_link($base)) {
            $findings[] = $this->finding('critical', 'Symbolic link found', $key . '/' . $version . ' is a symbolic link.', $key, $version);
        }
        if (!is_file($manifest)) {
            $findings[] = $this->finding('critical', 'Manifest missing', $key . '/' . $version . ' has no manifest.php.', $key, $version);
        } else {
            $source = (string)file_get_contents($manifest);
            if (!$this->parses($source)) {
                $findings[] = $this->finding('critical', 'Manifest syntax error', $key . '/' . $version . ' has a manifest.php that PHP cannot parse.', $key, $version);
            } elseif (!preg_match("/['\"]pluginVersion['\"]\\s*=>\\s*['\"]v?([^'\"]+)['\"]/", $this->withoutComments($source), $match)) {
                $findings[] = $this->finding('warning', 'Manifest version unreadable', $key . '/' . $version . ' does not declare pluginVersion in the expected form.', $key, $version);
            } elseif (version_compare(ltrim($version, 'vV'), ltrim($match[1], 'vV'), '!=')) {
                $findings[] = $this->finding('critical', 'Manifest version mismatch', $key . '/' . $version . ' declares v' . $match[1] . '.', $key, $version);
            }
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($base) + 1));
                if ($item->isLink()) {
                    $findings[] = $this->finding('critical', 'Symbolic link found', $key . '/' . $version . '/' . $relative . ' is a symbolic link.', $key, $version, $relative);
                    continue;
                }
                if ($item->isDir()) {
                    if ($this->isWorldWritable($item->getPerms())) {
                        $findings[] = $this->finding('warning', 'World-writable directory', $key . '/' . $version . '/' . $relative . ' is world writable.', $key, $version, $relative);
                    }
                    continue;
                }
                ++$fileCount;
                $bytes += max(0, (int)$item->getSize());
                $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
                if ($extension === 'php') {
                    ++$phpCount;
                    if ($this->isWorldWritable($item->getPerms())) {
                        $findings[] = $this->finding('critical', 'World-writable PHP file', $key . '/' . $version . '/' . $relative . ' is world writable.', $key, $version, $relative);
                    }
                    if ($this->needsGuardCheck($relative)) {
                        $head = (string)file_get_contents($item->getPathname(), false, null, 0, self::SOURCE_READ_BYTES);
                        if (!$this->hasDirectAccessProtection($head)) {
                            $findings[] = $this->finding('warning', 'PHP direct-access guard not detected', $key . '/' . $version . '/' . $relative . ' should be reviewed for direct web access.', $key, $version, $relative);
                        }
                    }
                } elseif (in_array($extension, ['css', 'gif', 'jpg', 'jpeg', 'js', 'png', 'svg', 'webp', 'woff', 'woff2'], true)) {
                    ++$staticCount;
                }
                if (in_array($extension, self::SENSITIVE_EXTENSIONS, true)) {
                    $findings[] = $this->finding('warning', 'Sensitive file type in package', $key . '/' . $version . '/' . $relative . ' may be exposed or unnecessary in production.', $key, $version, $relative);
                }
            }
        } catch (UnexpectedValueException $exception) {
            $findings[] = $this->finding('critical', 'Version directory unreadable', $key . '/' . $version . ' could not be read completely.', $key, $version);
        }

        return [
            'version' => $version,
            'files' => $fileCount,
            'php_files' => $phpCount,
            'static_assets' => $staticCount,
            'bytes' => $bytes,
            'findings' => $findings,
        ];
    }

    private function runtime(): array
    {
        $opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        $configuration = function_exists('opcache_get_configuration') ? @opcache_get_configuration() : false;
        $enabled = is_array($opcache) && !empty($opcache['opcache_enabled']);
        $findings = [];
        if (!$enabled) {
            $findings[] = $this->finding('warning', 'OPcache unavailable', 'OPcache is disabled, unavailable, or restricted for this PHP process.');
        } else {
            $findings[] = $this->finding('good', 'OPcache active', 'PHP bytecode caching is active.');
            $memory = $opcache['memory_usage'] ?? [];
            $used = (int)($memory['used_memory'] ?? 0);
            $free = (int)($memory['free_memory'] ?? 0);
            if ($used + $free > 0 && ($free / ($used + $free)) < 0.10) {
                $findings[] = $this->finding('warning', 'OPcache memory low', 'Less than 10 percent of OPcache memory is free.');
            }
            $stats = $opcache['opcache_statistics'] ?? [];
            if (!empty($stats['oom_restarts']) || !empty($stats['hash_restarts'])) {
                $findings[] = $this->finding('warning', 'OPcache restarts detected', 'OPcache reports memory or hash-table restarts.');
            }
        }

        $realpathSize = $this->iniBytes((string)ini_get('realpath_cache_size'));
        if ($realpathSize > 0 && $realpathSize < 4 * 1024 * 1024) {
            $findings[] = $this->finding('info', 'Small realpath cache', 'The realpath cache is below 4 MB. A host can review this on shops with many PHP files.');
        }

        return [
            'values' => [
                'php_version' => PHP_VERSION,
                'zen_cart_version' => defined('PROJECT_VERSION_MAJOR') ? PROJECT_VERSION_MAJOR . '.' . (defined('PROJECT_VERSION_MINOR') ? PROJECT_VERSION_MINOR : '') : 'unknown',
                'memory_limit' => (string)ini_get('memory_limit'),
                'realpath_cache_size' => (string)ini_get('realpath_cache_size'),
                'realpath_cache_ttl' => (string)ini_get('realpath_cache_ttl'),
                'opcache_enabled' => $enabled,
                'opcache_validate_timestamps' => is_array($configuration) ? ($configuration['directives']['opcache.validate_timestamps'] ?? null) : null,
                'opcache_memory' => is_array($opcache) ? ($opcache['memory_usage'] ?? null) : null,
                'opcache_statistics' => is_array($opcache) ? ($opcache['opcache_statistics'] ?? null) : null,
            ],
            'findings' => $findings,
        ];
    }

    private function directories(string $path): array
    {
        $directories = [];
        $items = @scandir($path);
        if (!is_array($items)) {
            return [];
        }
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && $item !== '' && $item[0] !== '.' && is_dir($path . DIRECTORY_SEPARATOR . $item)) {
                $directories[] = $item;
            }
        }
        natcasesort($directories);
        return array_values($directories);
    }

    private function needsGuardCheck(string $relative): bool
    {
        $relative = strtolower(str_replace('\\', '/', $relative));
        if (
            strpos($relative, 'installer/') === 0
            || strpos($relative, '/vendor/') !== false
            || strpos($relative, '/includes/languages/') !== false
        ) {
            return false;
        }
        return strpos($relative, 'catalog/') === 0 && strpos($relative, '/includes/templates/') === false;
    }

    private function hasDirectAccessProtection(string $head): bool
    {
        $code = $this->withoutComments($head);
        if (preg_match("/\\bdefined\\s*\\(\\s*['\"]IS_ADMIN_FLAG['\"]\\s*\\)/i", $code) === 1) {
            return true;
        }

        return preg_match(
            "/\\b(?:include|include_once|require|require_once)\\b[^;]*includes\\/application_top\\.php['\"]/i",
            $code
        ) === 1;
    }

    // Windows reports 0666 or 0777 for every file, so the world-write bit means nothing there.
    private function isWorldWritable(int $permissions, string $separator = DIRECTORY_SEPARATOR): bool
    {
        return $separator === '/' && ($permissions & 0002) !== 0;
    }

    private function parses(string $source): bool
    {
        if (!function_exists('token_get_all')) {
            return true;
        }
        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $exception) {
            return false;
        }
        return true;
    }

    private function withoutComments(string $source): string
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }
        $code = '';
        foreach (@token_get_all($source) as $token) {
            if (!is_array($token)) {
                $code .= $token;
            } elseif ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                $code .= $token[1];
            }
        }
        return $code;
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $number = (float)$value;
        switch (strtolower(substr($value, -1))) {
            case 'g':
                return (int)($number * 1024 * 1024 * 1024);
            case 'm':
                return (int)($number * 1024 * 1024);
            case 'k':
                return (int)($number * 1024);
            default:
                return (int)$number;
        }
    }

    private function finding(string $severity, string $title, string $detail, ?string $plugin = null, ?string $version = null, ?string $file = null): array
    {
        return array_filter([
            'severity' => $severity,
            'title' => $title,
            'detail' => $detail,
            'plugin' => $plugin,
            'version' => $version,
            'file' => $file,
        ], static fn($value): bool => $value !== null);
    }
}
