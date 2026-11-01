<?php
declare(strict_types=1);
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) { die('Illegal Access'); }

// Runs after the admin functions and language files load, so a translation wins over this text.
if (!defined('BOX_TOOLS_PLUGIN_HEALTH_GUARD')) { define('BOX_TOOLS_PLUGIN_HEALTH_GUARD', 'Plugin Health Guard'); }

if (!zen_page_key_exists('pluginHealthGuard')) {
    zen_register_admin_page('pluginHealthGuard', 'BOX_TOOLS_PLUGIN_HEALTH_GUARD', 'FILENAME_PLUGIN_HEALTH_GUARD', '', 'tools', 'Y', 99);
}
