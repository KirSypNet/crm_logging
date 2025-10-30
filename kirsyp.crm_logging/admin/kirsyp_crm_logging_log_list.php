<?php
/**
 * Страница в админке для просмотра логов
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Loader;

// Проверка прав
if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

// Подключение модуля
if (!Loader::includeModule('kirsyp.crm_logging')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
    ShowError('Модуль kirsyp.crm_logging не установлен');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
    die();
}

// Подключение логов
require($_SERVER['DOCUMENT_ROOT'] . '/local/modules/kirsyp.crm_logging/admin/log_list.php');
