<?php
/**
 * Класс для работы с логами
 */

namespace Kirsyp\CrmLogging;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

class LogTable
{
    private static $dataClass = null;

    /**
     * Класс для работы с HL блоком
     * @return object|null
     */
    public static function getDataClass()
    {
        if (self::$dataClass === null) {
            Loader::includeModule('highloadblock');

            $worker = new HighloadWorker();
            $hlBlock = $worker->getHighloadBlock();

            if ($hlBlock) {
                $entity = HighloadBlockTable::compileEntity($hlBlock);
                self::$dataClass = $entity->getDataClass();
            }
        }

        return self::$dataClass;
    }

    /**
     * Добавление записи в лог
     * @param array $data
     * @return bool
     */
    public static function add(array $data)
    {
        $dataClass = self::getDataClass();

        if (!$dataClass) {
            return false;
        }

        $result = $dataClass::add([
            'UF_USER_ID' => $data['USER_ID'],
            'UF_ENTITY_ID' => $data['ENTITY_ID'],
            'UF_ELEMENT_ID' => $data['ELEMENT_ID'],
            'UF_CHANGE_LOG' => $data['CHANGE_LOG'],
            'UF_DATE' => new \Bitrix\Main\Type\DateTime(),
        ]);

        return $result->isSuccess();
    }

    /**
     * Получение списка логов с фильтром
     * @param array $filter
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getList(array $filter = [], array $order = [], $limit = 0, $offset = 0)
    {
        $dataClass = self::getDataClass();

        if (!$dataClass) {
            return [];
        }

        $params = [
            'select' => ['*'],
            'order' => $order ?: ['ID' => 'DESC'],
        ];

        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        if ($limit > 0) {
            $params['limit'] = $limit;
            $params['offset'] = $offset;
        }

        $result = $dataClass::getList($params);
        $items = [];

        while ($item = $result->fetch()) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Подсчет количества записей
     * @param array $filter
     * @return int
     */
    public static function getCount(array $filter = [])
    {
        $dataClass = self::getDataClass();

        if (!$dataClass) {
            return 0;
        }

        $params = ['select' => ['CNT']];

        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        $params['runtime'] = [
            new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
        ];

        $result = $dataClass::getList($params)->fetch();

        return (int)$result['CNT'];
    }

    /**
     * Удаление записи
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        $dataClass = self::getDataClass();

        if (!$dataClass) {
            return false;
        }

        $result = $dataClass::delete($id);

        return $result->isSuccess();
    }

    /**
     * Удаление старых записей
     * @param int $days
     * @return int Количество удаленных записей
     */
    public static function deleteOldRecords($days)
    {
        $dataClass = self::getDataClass();

        if (!$dataClass) {
            return 0;
        }

        // Дата, старше которой нужно удалить записи
        $date = new \Bitrix\Main\Type\DateTime();
        $date->add('-' . $days . ' days');

        // Получение записей для удаления
        $result = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['<UF_DATE' => $date],
        ]);

        $deletedCount = 0;

        while ($item = $result->fetch()) {
            if (self::delete($item['ID'])) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
