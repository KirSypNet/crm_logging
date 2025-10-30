<?php
/**
 * Класс для работы с HighloadBlock
 */

namespace Kirsyp\CrmLogging;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

class HighloadWorker
{
    const HL_NAME = 'KirsypCrmLogging';
    const HL_TABLE_NAME = 'kirsyp_crm_logging';

    /**
     * Создание HighloadBlock
     * @throws \Exception
     */
    public function createHighloadBlock()
    {
        Loader::includeModule('highloadblock');

        // Проверка существования HL блока
        $hlBlock = $this->getHighloadBlock();
        if ($hlBlock) {
            return $hlBlock['ID'];
        }

        // Создание HL блока
        $result = HighloadBlockTable::add([
            'NAME' => self::HL_NAME,
            'TABLE_NAME' => self::HL_TABLE_NAME,
        ]);

        if (!$result->isSuccess()) {
            throw new \Exception(implode(', ', $result->getErrorMessages()));
        }

        $hlBlockId = $result->getId();

        // Добавление полей
        $this->addFields($hlBlockId);

        return $hlBlockId;
    }

    /**
     * Добавление полей
     * @param int $hlBlockId
     */
    private function addFields($hlBlockId)
    {
        $userTypeEntity = new \CUserTypeEntity();

        // Пользователь
        $userTypeEntity->Add([
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_USER_ID',
            'USER_TYPE_ID' => 'employee',
            'MANDATORY' => 'Y',
            'EDIT_FORM_LABEL' => ['ru' => 'Пользователь'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Пользователь'],
            'LIST_FILTER_LABEL' => ['ru' => 'Пользователь'],
            'ERROR_MESSAGE' => ['ru' => ''],
            'HELP_MESSAGE' => ['ru' => ''],
        ]);

        // Тип сущности
        $userTypeEntity->Add([
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_ENTITY_ID',
            'USER_TYPE_ID' => 'string',
            'MANDATORY' => 'Y',
            'EDIT_FORM_LABEL' => ['ru' => 'Тип сущности'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Тип сущности'],
            'LIST_FILTER_LABEL' => ['ru' => 'Тип сущности'],
            'ERROR_MESSAGE' => ['ru' => ''],
            'HELP_MESSAGE' => ['ru' => ''],
            'SETTINGS' => [
                'SIZE' => 50,
                'ROWS' => 1,
                'DEFAULT_VALUE' => '',
            ],
        ]);

        // ID элемента
        $userTypeEntity->Add([
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_ELEMENT_ID',
            'USER_TYPE_ID' => 'integer',
            'MANDATORY' => 'Y',
            'EDIT_FORM_LABEL' => ['ru' => 'ID элемента'],
            'LIST_COLUMN_LABEL' => ['ru' => 'ID элемента'],
            'LIST_FILTER_LABEL' => ['ru' => 'ID элемента'],
            'ERROR_MESSAGE' => ['ru' => ''],
            'HELP_MESSAGE' => ['ru' => ''],
            'SETTINGS' => [
                'DEFAULT_VALUE' => 0,
            ],
        ]);

        // Лог изменений
        $userTypeEntity->Add([
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_CHANGE_LOG',
            'USER_TYPE_ID' => 'string',
            'MANDATORY' => 'N',
            'EDIT_FORM_LABEL' => ['ru' => 'Лог изменений'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Лог изменений'],
            'LIST_FILTER_LABEL' => ['ru' => 'Лог изменений'],
            'ERROR_MESSAGE' => ['ru' => ''],
            'HELP_MESSAGE' => ['ru' => ''],
            'SETTINGS' => [
                'SIZE' => 0,
                'ROWS' => 10,
                'DEFAULT_VALUE' => '',
            ],
        ]);

        // Дата изменений
        $userTypeEntity->Add([
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_DATE',
            'USER_TYPE_ID' => 'datetime',
            'MANDATORY' => 'Y',
            'EDIT_FORM_LABEL' => ['ru' => 'Дата изменений'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Дата изменений'],
            'LIST_FILTER_LABEL' => ['ru' => 'Дата изменений'],
            'ERROR_MESSAGE' => ['ru' => ''],
            'HELP_MESSAGE' => ['ru' => ''],
        ]);
    }

    /**
     * Получение HL блока по имени
     * @return array|null
     */
    public function getHighloadBlock()
    {
        Loader::includeModule('highloadblock');

        $result = HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_NAME]
        ]);

        return $result->fetch();
    }

    /**
     * Удаление HighloadBlock
     */
    public function deleteHighloadBlock()
    {
        $hlBlock = $this->getHighloadBlock();

        if ($hlBlock) {
            // Получаем имя таблицы
            $tableName = $hlBlock['TABLE_NAME'];

            // Удаляем HL блок
            $result = HighloadBlockTable::delete($hlBlock['ID']);

            if ($result->isSuccess()) {
                // Удаляем таблицу из БД вручную, если она еще существует
                global $DB;
                $DB->Query("DROP TABLE IF EXISTS `{$tableName}`", true);
            }
        }
    }
}
