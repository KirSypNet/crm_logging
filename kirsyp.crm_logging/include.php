<?php
/**
 * Подключение модуля
 */

use Bitrix\Main\Loader;

// Автозагрузка классов
Loader::registerAutoLoadClasses(
    'kirsyp.crm_logging',
    [
        'Kirsyp\\CrmLogging\\HighloadWorker' => 'lib/HighloadWorker.php',
        'Kirsyp\\CrmLogging\\EventHandler' => 'lib/EventHandler.php',
        'Kirsyp\\CrmLogging\\Agent' => 'lib/Agent.php',
        'Kirsyp\\CrmLogging\\LogTable' => 'lib/LogTable.php',
    ]
);
