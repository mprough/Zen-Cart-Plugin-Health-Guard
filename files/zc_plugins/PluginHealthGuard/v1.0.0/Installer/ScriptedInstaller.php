<?php
declare(strict_types=1);
if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    public string $pluginKey = 'PluginHealthGuard';
    public string $version = '1.0.0';

    protected function executeInstall(): bool
    {
        $this->executeInstallerSql(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function) VALUES ('Installed version', 'PLUGIN_HEALTH_GUARD_VERSION', '1.0.0', 'Installed Plugin Health Guard version.', 0, 0, 'zen_cfg_select_option(array(\\'1.0.0\\'),')"
        );
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '1.0.0', set_function = 'zen_cfg_select_option(array(\\'1.0.0\\'),' WHERE configuration_key = 'PLUGIN_HEALTH_GUARD_VERSION'"
        );
        zen_deregister_admin_pages(['pluginHealthGuard']);
        zen_register_admin_page('pluginHealthGuard', 'BOX_TOOLS_PLUGIN_HEALTH_GUARD', 'FILENAME_PLUGIN_HEALTH_GUARD', '', 'tools', 'Y', 99);
        return true;
    }

    protected function executeUpgrade(...$args): bool { return $this->executeInstall(); }

    protected function executeUninstall(): bool
    {
        $this->executeInstallerSql("DELETE FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = 'pluginHealthGuard'");
        $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'PLUGIN_HEALTH_GUARD_VERSION'");
        return true;
    }
}
