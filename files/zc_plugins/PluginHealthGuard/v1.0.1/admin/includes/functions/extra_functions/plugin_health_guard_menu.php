<?php
declare(strict_types=1);
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) { die('Illegal Access'); }
if (!defined('FILENAME_PLUGIN_HEALTH_GUARD')) { define('FILENAME_PLUGIN_HEALTH_GUARD', 'plugin_health_guard'); }
if (!defined('BOX_TOOLS_PLUGIN_HEALTH_GUARD')) { define('BOX_TOOLS_PLUGIN_HEALTH_GUARD', 'Plugin Health Guard'); }

$pluginHealthGuardInstalled = defined('PLUGIN_HEALTH_GUARD_VERSION');
if (!$pluginHealthGuardInstalled && isset($db)) {
    $pluginHealthGuardVersion = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'PLUGIN_HEALTH_GUARD_VERSION' LIMIT 1");
    $pluginHealthGuardInstalled = !$pluginHealthGuardVersion->EOF;
}
if (function_exists('zen_register_admin_page') && $pluginHealthGuardInstalled && !zen_page_key_exists('pluginHealthGuard')) {
    zen_register_admin_page('pluginHealthGuard', 'BOX_TOOLS_PLUGIN_HEALTH_GUARD', 'FILENAME_PLUGIN_HEALTH_GUARD', '', 'tools', 'Y', 99);
}
unset($pluginHealthGuardInstalled, $pluginHealthGuardVersion);
