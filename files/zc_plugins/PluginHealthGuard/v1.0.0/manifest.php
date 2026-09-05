<?php
declare(strict_types=1);
if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Zen Cart Plugin Health Guard',
    'pluginDescription' => 'Read-only health and safety checks for encapsulated plugins and their PHP runtime.',
    'pluginAuthor' => 'Melanie Prough, PRO-Webs.net',
    'pluginId' => 0,
    'zcVersions' => ['v200', 'v210', 'v220'],
    'changelog' => 'https://github.com/mprough/Zen-Cart-Plugin-Health-Guard/blob/main/CHANGELOG.md',
    'github_repo' => 'https://github.com/mprough/Zen-Cart-Plugin-Health-Guard',
    'pluginGroups' => [],
];
