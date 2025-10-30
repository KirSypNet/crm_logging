<?php
/**
 * Настройки модуля
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

if (!$USER->IsAdmin()) {
    return;
}

$module_id = 'kirsyp.crm_logging';

Loader::includeModule($module_id);
Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

// Сохранение настроек
if ($request->isPost() && $request->get('save') && check_bitrix_sessid()) {
    $days = (int)$request->getPost('kirsyp_crm_logging_remove_setting');

    if ($days > 0) {
        Option::set($module_id, 'kirsyp_crm_logging_remove_setting', $days);
        \CAdminMessage::ShowNote(Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_SAVED'));
    } else {
        \CAdminMessage::ShowMessage(Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_ERROR_DAYS'));
    }
}

// Получаем текущее значение
$days = (int)Option::get($module_id, 'kirsyp_crm_logging_remove_setting', 30);

// Вывод формы настроек
$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_TAB'),
        'TITLE' => Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_TAB_TITLE'),
    ],
]);

?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->Begin(); ?>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%" class="adm-detail-content-cell-l">
            <label for="kirsyp_crm_logging_remove_setting">
                <?= Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_DAYS_LABEL') ?>:
            </label>
        </td>
        <td width="60%" class="adm-detail-content-cell-r">
            <input type="number"
                   id="kirsyp_crm_logging_remove_setting"
                   name="kirsyp_crm_logging_remove_setting"
                   value="<?= htmlspecialcharsbx($days) ?>"
                   min="1"
                   size="10">
            <br>
            <small><?= Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_DAYS_HINT') ?></small>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>

    <input type="submit" name="save" value="<?= Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_SAVE') ?>" class="adm-btn-save">
    <input type="reset" name="reset" value="<?= Loc::getMessage('KIRSYP_CRM_LOGGING_OPTIONS_RESET') ?>">

    <?php $tabControl->End(); ?>
</form>
