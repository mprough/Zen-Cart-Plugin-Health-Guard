<?php

declare(strict_types=1);

require __DIR__ . '/../files/zc_plugins/PluginHealthGuard/v1.0.3/admin/includes/classes/PluginHealthGuardScanner.php';

$fixture = sys_get_temp_dir() . '/phg-test-' . bin2hex(random_bytes(6));
mkdir($fixture . '/GoodPlugin/v1.0.0/catalog/includes/classes', 0777, true);
mkdir($fixture . '/GoodPlugin/v1.0.0/catalog/includes/languages/english/extra_definitions', 0777, true);
mkdir($fixture . '/GoodPlugin/v0.9.0', 0777, true);
file_put_contents($fixture . '/GoodPlugin/v1.0.0/manifest.php', "<?php return ['pluginVersion' => 'v1.0.0'];");
file_put_contents($fixture . '/GoodPlugin/v1.0.0/catalog/includes/classes/guarded.php', "<?php if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }");
file_put_contents($fixture . '/GoodPlugin/v1.0.0/catalog/endpoint.php', "<?php include \"includes/application_top.php\";");
file_put_contents($fixture . '/GoodPlugin/v1.0.0/catalog/includes/classes/unguarded.php', "<?php class Example {}");
file_put_contents(
    $fixture . '/GoodPlugin/v1.0.0/catalog/includes/languages/english/extra_definitions/example.php',
    "<?php define('TEXT_EXAMPLE', 'Example');"
);
file_put_contents($fixture . '/GoodPlugin/v0.9.0/manifest.php', "<?php return ['pluginVersion' => 'v0.9.0'];");
file_put_contents($fixture . '/GoodPlugin/v0.9.0/debug.log', 'test');

$scanner = new PluginHealthGuardScanner($fixture, ['GoodPlugin' => ['version' => 'v1.0.0', 'status' => 1]]);
$report = $scanner->scan();
$titles = array_column($report['findings'], 'title');

assert(in_array('Multiple versions retained', $titles, true));
assert(in_array('Sensitive file type in package', $titles, true));
assert(count($report['plugins']) === 1);
assert($report['plugins'][0]['installed_version'] === 'v1.0.0');
assert(!in_array('PHP direct-access guard not detected', array_column(array_filter(
    $report['findings'],
    static fn(array $finding): bool => ($finding['file'] ?? '') === 'catalog/includes/languages/english/extra_definitions/example.php'
), 'title'), true));
assert(!in_array('PHP direct-access guard not detected', array_column(array_filter(
    $report['findings'],
    static fn(array $finding): bool => ($finding['file'] ?? '') === 'catalog/endpoint.php'
), 'title'), true));
assert(in_array('PHP direct-access guard not detected', array_column(array_filter(
    $report['findings'],
    static fn(array $finding): bool => ($finding['file'] ?? '') === 'catalog/includes/classes/unguarded.php'
), 'title'), true));

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($iterator as $item) {
    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
}
rmdir($fixture);
echo "Scanner tests passed\n";
