<?php
declare(strict_types=1);
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) { die('Illegal Access'); }
$autoLoadConfig[200][] = ['autoType' => 'init_script', 'loadFile' => 'init_plugin_health_guard.php'];
