<?php

declare(strict_types=1);

require 'includes/application_top.php';

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

require_once DIR_FS_CATALOG . 'zc_plugins/PluginHealthGuard/v1.0.0/admin/includes/classes/PluginHealthGuardScanner.php';

$installedPlugins = [];
if (defined('TABLE_PLUGIN_CONTROL')) {
    $pluginRows = $db->Execute('SELECT unique_key, version, status FROM ' . TABLE_PLUGIN_CONTROL);
    foreach ($pluginRows as $pluginRow) {
        if ((int)($pluginRow['status'] ?? 0) === 1) {
            $installedPlugins[(string)$pluginRow['unique_key']] = $pluginRow;
        }
    }
}

$scanner = new PluginHealthGuardScanner(DIR_FS_CATALOG . 'zc_plugins', $installedPlugins);
$report = $scanner->scan();

if (($_GET['action'] ?? '') === 'export') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="plugin-health-guard-' . gmdate('Ymd-His') . '.json"');
    header('Cache-Control: no-store, max-age=0');
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    require DIR_WS_INCLUDES . 'application_bottom.php';
    exit;
}

function phg_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, CHARSET);
}

$severityLabels = ['critical' => 'Critical', 'warning' => 'Review', 'info' => 'Information', 'good' => 'Good'];
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    <style>
        .phg-summary { display:flex; flex-wrap:wrap; gap:12px; margin:18px 0; }
        .phg-card { background:#fff; border:1px solid #d7d7d7; border-radius:4px; padding:14px 18px; min-width:130px; }
        .phg-card strong { display:block; font-size:24px; }
        .phg-critical { border-left:5px solid #b42318; }
        .phg-warning { border-left:5px solid #d97706; }
        .phg-info { border-left:5px solid #2563eb; }
        .phg-good { border-left:5px solid #15803d; }
        .phg-table td, .phg-table th { vertical-align:top !important; }
        .phg-path { font-family:monospace; overflow-wrap:anywhere; }
        .phg-note { background:#f4f7fa; border:1px solid #d8e0e8; padding:12px; margin:12px 0; }
    </style>
</head>
<body>
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<div class="container-fluid">
    <h1><?php echo phg_h(HEADING_TITLE); ?></h1>
    <p><?php echo phg_h(TEXT_PAGE_INTRO); ?></p>
    <p>
        <a class="btn btn-primary" href="<?php echo phg_h(zen_href_link(FILENAME_PLUGIN_HEALTH_GUARD)); ?>"><?php echo phg_h(TEXT_RUN_AGAIN); ?></a>
        <a class="btn btn-default" href="<?php echo phg_h(zen_href_link(FILENAME_PLUGIN_HEALTH_GUARD, 'action=export')); ?>"><?php echo phg_h(TEXT_EXPORT_JSON); ?></a>
    </p>

    <div class="phg-summary">
        <?php foreach ($report['summary'] as $severity => $count) { ?>
            <div class="phg-card phg-<?php echo phg_h($severity); ?>">
                <span><?php echo phg_h($severityLabels[$severity]); ?></span>
                <strong><?php echo (int)$count; ?></strong>
            </div>
        <?php } ?>
        <div class="phg-card"><span>Plugins found</span><strong><?php echo count($report['plugins']); ?></strong></div>
        <div class="phg-card"><span>Scan time</span><strong><?php echo phg_h($report['duration_ms']); ?> ms</strong></div>
    </div>

    <div class="phg-note"><strong>Important:</strong> Findings are diagnostic. Review a plugin before removing files or changing server settings. This tool never makes those changes.</div>

    <h2>Findings</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered phg-table">
            <thead><tr><th>Level</th><th>Finding</th><th>Details</th></tr></thead>
            <tbody>
            <?php if ($report['findings'] === []) { ?>
                <tr><td colspan="3">No findings.</td></tr>
            <?php } ?>
            <?php foreach ($report['findings'] as $finding) { ?>
                <tr class="phg-<?php echo phg_h($finding['severity']); ?>">
                    <td><?php echo phg_h($severityLabels[$finding['severity']] ?? $finding['severity']); ?></td>
                    <td><strong><?php echo phg_h($finding['title']); ?></strong></td>
                    <td><?php echo phg_h($finding['detail']); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <h2>Plugin inventory</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered phg-table">
            <thead><tr><th>Plugin key</th><th>Plugin Manager</th><th>Directories</th><th>Files</th><th>Size</th></tr></thead>
            <tbody>
            <?php foreach ($report['plugins'] as $plugin) {
                $files = array_sum(array_column($plugin['versions'], 'files'));
                $bytes = array_sum(array_column($plugin['versions'], 'bytes'));
                ?>
                <tr>
                    <td class="phg-path"><?php echo phg_h($plugin['key']); ?></td>
                    <td><?php echo $plugin['installed_version'] !== null ? 'Active: ' . phg_h($plugin['installed_version']) : 'Not active'; ?></td>
                    <td><?php echo phg_h(implode(', ', array_column($plugin['versions'], 'version'))); ?></td>
                    <td><?php echo (int)$files; ?></td>
                    <td><?php echo phg_h(number_format($bytes / 1024, 1)); ?> KB</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <h2>PHP runtime</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered phg-table">
            <tbody>
            <?php foreach ($report['runtime'] as $name => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_SLASHES);
                } elseif (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif ($value === null) {
                    $value = 'Unavailable';
                }
                ?>
                <tr><th><?php echo phg_h(str_replace('_', ' ', ucfirst($name))); ?></th><td class="phg-path"><?php echo phg_h($value); ?></td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
