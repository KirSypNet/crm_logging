<?php
/**
 * Агент
 */

namespace Kirsyp\CrmLogging;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class Agent
{
    /**
     * Очистка старых логов
     * @return string
     */
    public static function cleanOldLogs()
    {
        if (!Loader::includeModule('kirsyp.crm_logging')) {
            return '';
        }

        // Количество дней хранения
        $days = (int)Option::get('kirsyp.crm_logging', 'kirsyp_crm_logging_remove_setting', 30);

        if ($days <= 0) {
            return '\Kirsyp\CrmLogging\Agent::cleanOldLogs();';
        }

        // Удаление старых записей
        $deletedCount = LogTable::deleteOldRecords($days);

        // Запись в журнал событий
        if ($deletedCount > 0) {
            \Bitrix\Main\EventLog::add([
                'SEVERITY' => 'INFO',
                'AUDIT_TYPE_ID' => 'KIRSYP_CRM_LOGGING_CLEAN',
                'MODULE_ID' => 'kirsyp.crm_logging',
                'DESCRIPTION' => 'Удалено записей логов: ' . $deletedCount,
            ]);
        }

        return '\Kirsyp\CrmLogging\Agent::cleanOldLogs();';
    }
}
