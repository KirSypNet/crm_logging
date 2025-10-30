<?php
/**
 * Класс установки модуля kirsyp.crm_logging
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

Loc::loadMessages(__FILE__);

class kirsyp_crm_logging extends CModule
{
    public $MODULE_ID = 'kirsyp.crm_logging';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('KIRSYP_CRM_LOGGING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('KIRSYP_CRM_LOGGING_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('KIRSYP_CRM_LOGGING_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('KIRSYP_CRM_LOGGING_PARTNER_URI');
    }

    /**
     * Установка модуля
     */
    public function DoInstall()
    {
        global $APPLICATION;

        if ($this->isVersionD7()) {
            // Регистрация модуля
            ModuleManager::registerModule($this->MODULE_ID);

            // Классы модуля
            Loader::includeModule($this->MODULE_ID);

            // Файлов
            $this->installFiles();

            // HighloadBlock
            $this->installHighloadBlock();

            // События
            $this->registerEvents();

            // Агента
            $this->registerAgent();

            // Настройки по умолчанию
            Option::set($this->MODULE_ID, 'kirsyp_crm_logging_remove_setting', 30);

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KIRSYP_CRM_LOGGING_INSTALL_TITLE'),
                __DIR__ . '/step.php'
            );
        } else {
            $APPLICATION->ThrowException(
                Loc::getMessage('KIRSYP_CRM_LOGGING_INSTALL_ERROR_VERSION')
            );
        }

        return true;
    }

    /**
     * Удаление модуля
     */
    public function DoUninstall()
    {
        global $APPLICATION;

        $request = Application::getInstance()->getContext()->getRequest();

        // Подтверждения удаления
        if ($request->get('step') < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_TITLE'),
                __DIR__ . '/unstep1.php'
            );
        } elseif ($request->get('step') == 2) {
            // Подключаем модуль
            Loader::includeModule($this->MODULE_ID);

            // Отмена регистрации событий
            $this->unRegisterEvents();

            // Удаление агента
            $this->unRegisterAgent();

            // Удаление HighloadBlock если установлена галочка
            if ($request->get('savedata') != 'Y') {
                $this->unInstallHighloadBlock();
            }

            // Удаление файлов
            $this->unInstallFiles();

            // Удаление настроек модуля
            Option::delete($this->MODULE_ID);

            // Удаление модуля
            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_TITLE'),
                __DIR__ . '/unstep2.php'
            );
        }

        return true;
    }

    /**
     * Копирование файлов модуля
     */
    public function installFiles()
    {
        copy(__DIR__ . "/../admin/kirsyp_crm_logging_log_list.php", Application::getDocumentRoot() . "/bitrix/admin/kirsyp_crm_logging_log_list.php");

        return true;
    }

    /**
     * Удаление файлов модуля
     */
    public function unInstallFiles()
    {
        unlink(Application::getDocumentRoot() . "/bitrix/admin/kirsyp_crm_logging_log_list.php");

        return true;
    }

    /**
     * Создание HighloadBlock
     */
    private function installHighloadBlock()
    {
        if (!ModuleManager::isModuleInstalled('highloadblock')) {
            throw new \Exception(Loc::getMessage('KIRSYP_CRM_LOGGING_ERROR_HIGHLOAD_NOT_INSTALLED'));
        }

        Loader::includeModule('highloadblock');

        $worker = new \Kirsyp\CrmLogging\HighloadWorker();
        $worker->createHighloadBlock();
    }

    /**
     * Удаление HighloadBlock
     */
    private function unInstallHighloadBlock()
    {
        if (ModuleManager::isModuleInstalled('highloadblock')) {
            Loader::includeModule('highloadblock');

            $worker = new \Kirsyp\CrmLogging\HighloadWorker();
            $worker->deleteHighloadBlock();
        }
    }

    /**
     * Регистрация обработчиков событий
     */
    private function registerEvents()
    {
        $eventManager = EventManager::getInstance();

        // Лиды
        $eventManager->registerEventHandler('crm', 'OnBeforeCrmLeadUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onBeforeCrmLeadUpdate');
        
        $eventManager->registerEventHandler('crm', 'OnAfterCrmLeadUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onAfterCrmLeadUpdate');
        
        // Сделки
        $eventManager->registerEventHandler('crm', 'OnBeforeCrmDealUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onBeforeCrmDealUpdate');
        
        $eventManager->registerEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onAfterCrmDealUpdate');
        
        // Компании
        $eventManager->registerEventHandler('crm', 'OnBeforeCrmCompanyUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onBeforeCrmCompanyUpdate');
        
        $eventManager->registerEventHandler('crm', 'OnAfterCrmCompanyUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onAfterCrmCompanyUpdate');
    }

    /**
     * Отмена регистрации обработчиков событий
     */
    private function unRegisterEvents()
    {
        $eventManager = EventManager::getInstance();

        // Лиды
        $eventManager->unRegisterEventHandler('crm', 'OnBeforeCrmLeadUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onBeforeCrmLeadUpdate');
        
        $eventManager->unRegisterEventHandler('crm', 'OnAfterCrmLeadUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onAfterCrmLeadUpdate');
        
        // Сделки
        $eventManager->unRegisterEventHandler('crm', 'OnBeforeCrmDealUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onBeforeCrmDealUpdate');
        
        $eventManager->unRegisterEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onAfterCrmDealUpdate');
        
        // Компании
        $eventManager->unRegisterEventHandler('crm', 'OnBeforeCrmCompanyUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onBeforeCrmCompanyUpdate');
        
        $eventManager->unRegisterEventHandler('crm', 'OnAfterCrmCompanyUpdate', $this->MODULE_ID, 
            'Kirsyp\\CrmLogging\\EventHandler', 'onAfterCrmCompanyUpdate');
    }

    /**
     * Регистрация агента
     */
    private function registerAgent()
    {
        \CAgent::AddAgent(
            'Kirsyp\\CrmLogging\\Agent::cleanOldLogs();',
            $this->MODULE_ID,
            'N',
            86400,
            '',
            'Y',
            '',
            30
        );
    }

    /**
     * Удаление агента
     */
    private function unRegisterAgent()
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    /**
     * Проверка версии ядра D7
     */
    private function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }
}
