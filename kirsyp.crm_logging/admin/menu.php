<?php
/**
 * Панель админки
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// Проверка прав
if (!$USER->IsAdmin()) {
    return [];
}

return [
    [
        'parent_menu' => 'global_menu_services', // Меню "Сервисы"
        'section' => 'kirsyp_crm_logging',
        'sort' => 100,
        'text' => Loc::getMessage('KIRSYP_CRM_LOGGING_MENU_TITLE'),
        'title' => Loc::getMessage('KIRSYP_CRM_LOGGING_MENU_TITLE'),
        'icon' => 'sys_menu_icon',
        'page_icon' => 'sys_page_icon',
        'items_id' => 'menu_kirsyp_crm_logging',
        'items' => [
            [
                'text' => Loc::getMessage('KIRSYP_CRM_LOGGING_MENU_LOG_LIST'),
                'title' => Loc::getMessage('KIRSYP_CRM_LOGGING_MENU_LOG_LIST'),
                'url' => 'kirsyp_crm_logging_log_list.php?lang=' . LANGUAGE_ID,
                'more_url' => ['kirsyp_crm_logging_log_list.php'],
                'icon' => 'sys_menu_icon',
            ],
            [
                'text' => Loc::getMessage('KIRSYP_CRM_LOGGING_MENU_SETTINGS'),
                'title' => Loc::getMessage('KIRSYP_CRM_LOGGING_MENU_SETTINGS'),
                'url' => 'settings.php?lang=' . LANGUAGE_ID . '&mid=kirsyp.crm_logging',
                'icon' => 'sys_menu_icon',
            ],
        ],
    ],
];
