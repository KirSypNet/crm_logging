<?php
/**
 * Обработчики событий
 */

namespace Kirsyp\CrmLogging;

use Bitrix\Main\Loader;

class EventHandler
{
    // Значения до изменения
    private static $oldValues = [];

    /**
     * Обработчик перед изменением Лида
     * @param array $fields
     */
    public static function onBeforeCrmLeadUpdate(&$fields)
    {
        if (empty($fields['ID'])) {
            return;
        }

        self::saveOldValues('LEAD', $fields['ID']);
    }

    /**
     * Обработчик после изменения Лида
     * @param array $fields Новые значения полей
     */
    public static function onAfterCrmLeadUpdate(&$fields)
    {
        if (empty($fields['ID'])) {
            return;
        }

        self::logChanges('CRM_LEAD', $fields['ID']);
    }

    /**
     * Обработчик перед изменением Сделки
     * @param array $fields
     */
    public static function onBeforeCrmDealUpdate(&$fields)
    {
        if (empty($fields['ID'])) {
            return;
        }

        self::saveOldValues('DEAL', $fields['ID']);
    }

    /**
     * Обработчик после изменения Сделки
     * @param array $fields Новые значения полей
     */
    public static function onAfterCrmDealUpdate(&$fields)
    {
        if (empty($fields['ID'])) {
            return;
        }

        self::logChanges('CRM_DEAL', $fields['ID']);
    }

    /**
     * Обработчик перед изменением Компании
     * @param array $fields
     */
    public static function onBeforeCrmCompanyUpdate(&$fields)
    {
        if (empty($fields['ID'])) {
            return;
        }

        self::saveOldValues('COMPANY', $fields['ID']);
    }

    /**
     * Обработчик после изменения Компании
     * @param array $fields Новые значения полей
     */
    public static function onAfterCrmCompanyUpdate(&$fields)
    {
        if (empty($fields['ID'])) {
            return;
        }

        self::logChanges('CRM_COMPANY', $fields['ID']);
    }

    /**
     * Сохранение старых значений
     * @param string $entityType Тип сущности (LEAD, DEAL, COMPANY)
     * @param int $elementId ID элемента
     */
    private static function saveOldValues($entityType, $elementId)
    {
        Loader::includeModule('crm');

        $oldFields = null;

        switch ($entityType) {
            case 'LEAD':
                $dbResult = \CCrmLead::GetListEx(
                    [],
                    ['=ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['*', 'UF_*']
                );
                $oldFields = $dbResult->Fetch();
                break;

            case 'DEAL':
                $dbResult = \CCrmDeal::GetListEx(
                    [],
                    ['=ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['*', 'UF_*']
                );
                $oldFields = $dbResult->Fetch();
                break;

            case 'COMPANY':
                $dbResult = \CCrmCompany::GetListEx(
                    [],
                    ['=ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['*', 'UF_*']
                );
                $oldFields = $dbResult->Fetch();
                break;
        }

        if ($oldFields) {
            $key = $entityType . '_' . $elementId;
            self::$oldValues[$key] = $oldFields;
        }
    }

    /**
     * Логирование изменений
     * @param string $entityType Тип сущности (CRM_LEAD, CRM_DEAL, CRM_COMPANY)
     * @param int $elementId ID элемента
     */
    private static function logChanges($entityType, $elementId)
    {
        global $USER;

        // Получаем сохраненные старые значения
        $shortType = str_replace('CRM_', '', $entityType);
        $key = $shortType . '_' . $elementId;

        if (!isset(self::$oldValues[$key])) {
            return;
        }

        $oldFields = self::$oldValues[$key];

        // Получение новых значений
        $newFields = self::getNewFields($shortType, $elementId);

        if (!$newFields) {
            return;
        }

        // Поиск изменений
        $changes = self::findChanges($oldFields, $newFields);

        if (empty($changes)) {
            // Очищаем старые значения
            unset(self::$oldValues[$key]);
            return;
        }

        // ID пользователя
        $userId = 0;
        if (is_object($USER) && method_exists($USER, 'GetID')) {
            $userId = (int)$USER->GetID();
        }

        if ($userId <= 0 && isset($newFields['MODIFY_BY_ID'])) {
            $userId = (int)$newFields['MODIFY_BY_ID'];
        }

        if ($userId <= 0) {
            $userId = 1;
        }

        // Сохранение лога
        LogTable::add([
            'USER_ID' => $userId,
            'ENTITY_ID' => $entityType,
            'ELEMENT_ID' => $elementId,
            'CHANGE_LOG' => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        // Очищаем старые значения
        unset(self::$oldValues[$key]);
    }

    /**
     * Получение новых значений полей после обновления
     * @param string $entityType Тип сущности (LEAD, DEAL, COMPANY)
     * @param int $elementId ID элемента
     * @return array|null
     */
    private static function getNewFields($entityType, $elementId)
    {
        Loader::includeModule('crm');

        $newFields = null;

        switch ($entityType) {
            case 'LEAD':
                $dbResult = \CCrmLead::GetListEx(
                    [],
                    ['=ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['*', 'UF_*']
                );
                $newFields = $dbResult->Fetch();
                break;

            case 'DEAL':
                $dbResult = \CCrmDeal::GetListEx(
                    [],
                    ['=ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['*', 'UF_*']
                );
                $newFields = $dbResult->Fetch();
                break;

            case 'COMPANY':
                $dbResult = \CCrmCompany::GetListEx(
                    [],
                    ['=ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['*', 'UF_*']
                );
                $newFields = $dbResult->Fetch();
                break;
        }

        return $newFields;
    }

    /**
     * Поиск изменений между старыми и новыми полями
     * @param array $oldFields Старые значения
     * @param array $newFields Новые значения
     * @return array Массив изменений
     */
    private static function findChanges($oldFields, $newFields)
    {
        $changes = [];

        // Исключаемые служебные поля
        $excludeFields = [
            'ID',
            'DATE_CREATE',
            'DATE_MODIFY',
            'MODIFY_BY_ID',
            'CREATED_BY_ID',
            'DATE_MODIFY_TIMESTAMP',
            'DATE_CREATE_TIMESTAMP',
            'TIMESTAMP_X',
            'TIMESTAMP_X_UNIX',
        ];

        // Получаем все поля из обоих массивов
        $allFields = array_unique(array_merge(array_keys($oldFields), array_keys($newFields)));

        foreach ($allFields as $field) {
            // Пропуск исключаемых полей
            if (in_array($field, $excludeFields)) {
                continue;
            }

            // Получаем старое и новое значение
            $oldValue = isset($oldFields[$field]) ? $oldFields[$field] : null;
            $newValue = isset($newFields[$field]) ? $newFields[$field] : null;

            // Сравнение значений
            if (self::isDifferent($oldValue, $newValue)) {
                $changes[$field] = [
                    'old' => self::formatValue($field, $oldValue),
                    'new' => self::formatValue($field, $newValue),
                ];
            }
        }

        return $changes;
    }

    /**
     * Проверка различия значений
     * @param mixed $oldValue Старое значение
     * @param mixed $newValue Новое значение
     * @return bool
     */
    private static function isDifferent($oldValue, $newValue)
    {
        // Обработка null
        if ($oldValue === null && $newValue === null) {
            return false;
        }

        if ($oldValue === null || $newValue === null) {
            return true;
        }

        // Обработка массивов
        if (is_array($oldValue) && is_array($newValue)) {
            return serialize($oldValue) !== serialize($newValue);
        }

        if (is_array($oldValue) || is_array($newValue)) {
            return true;
        }

        // Приведение к строке для сравнения
        $oldStr = (string)$oldValue;
        $newStr = (string)$newValue;

        return $oldStr !== $newStr;
    }

    /**
     * Форматирование для записи в лог
     * @param string $fieldName Название поля
     * @param mixed $value Значение
     * @return string
     */
    private static function formatValue($fieldName, $value)
    {
        // Если значение пустое
        if ($value === null || $value === '') {
            return '[Пустое значение]';
        }

        // Обработка массивов
        if (is_array($value)) {
            // Для множественных полей
            if (!empty($value)) {
                return implode(', ', $value);
            }
            return '[Пустой массив]';
        }

        // Даты
        if (preg_match('/DATE/', $fieldName) && strtotime($value)) {
            return date('d.m.Y H:i:s', strtotime($value));
        }

        // Булевые значения
        if ($value === 'Y' || $value === 'N') {
            return $value === 'Y' ? 'Да' : 'Нет';
        }

        // ID типов, стадий и т.д.
        if (preg_match('/_ID$/', $fieldName) || preg_match('/^TYPE_ID$/', $fieldName)) {
            $readable = self::getReadableValue($fieldName, $value);
            if ($readable) {
                return $readable . ' [' . $value . ']';
            }
        }

        // Статусы
        if (preg_match('/STATUS/', $fieldName) || $fieldName === 'STAGE_ID') {
            $readable = self::getStatusName($fieldName, $value);
            if ($readable) {
                return $readable . ' [' . $value . ']';
            }
        }

        return (string)$value;
    }

    /**
     * Получение значения для ID полей
     * @param string $fieldName Название поля
     * @param mixed $value Значение
     * @return string|null
     */
    private static function getReadableValue($fieldName, $value)
    {
        if (!$value) {
            return null;
        }

        Loader::includeModule('crm');

        // Пользователи
        if (in_array($fieldName, ['ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'])) {
            $user = \CUser::GetByID($value)->Fetch();
            if ($user) {
                return trim($user['NAME'] . ' ' . $user['LAST_NAME']);
            }
        }

        // Компания
        if ($fieldName === 'COMPANY_ID') {
            $company = \CCrmCompany::GetByID($value);
            if ($company) {
                return $company['TITLE'];
            }
        }

        // Контакт
        if ($fieldName === 'CONTACT_ID') {
            $contact = \CCrmContact::GetByID($value);
            if ($contact) {
                return trim($contact['NAME'] . ' ' . $contact['LAST_NAME']);
            }
        }

        // Лид
        if ($fieldName === 'LEAD_ID') {
            $lead = \CCrmLead::GetByID($value);
            if ($lead) {
                return $lead['TITLE'];
            }
        }

        // Тип компании
        if ($fieldName === 'COMPANY_TYPE') {
            $types = \CCrmStatus::GetStatusList('COMPANY_TYPE');
            return isset($types[$value]) ? $types[$value] : null;
        }

        // Источник
        if ($fieldName === 'SOURCE_ID') {
            $sources = \CCrmStatus::GetStatusList('SOURCE');
            return isset($sources[$value]) ? $sources[$value] : null;
        }

        return null;
    }

    /**
     * Получение названия статуса/стадии
     * @param string $fieldName Название поля
     * @param string $value Значение
     * @return string|null
     */
    private static function getStatusName($fieldName, $value)
    {
        if (!$value) {
            return null;
        }

        Loader::includeModule('crm');

        // Стадия сделки
        if ($fieldName === 'STAGE_ID') {
            $stages = \CCrmStatus::GetStatusList('DEAL_STAGE');
            return isset($stages[$value]) ? $stages[$value] : null;
        }

        // Статус лида
        if ($fieldName === 'STATUS_ID') {
            $statuses = \CCrmStatus::GetStatusList('STATUS');
            return isset($statuses[$value]) ? $statuses[$value] : null;
        }

        return null;
    }
}
