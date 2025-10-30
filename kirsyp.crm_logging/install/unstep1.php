<?php
/**
 * Подтверждение удаления модуля
 */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}

Loc::loadMessages(__FILE__);
?>

<form action="<?= $APPLICATION->GetCurPage() ?>" method="get">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="kirsyp.crm_logging">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">

    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_WARNING') ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>

    <p><?= Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_NOTICE') ?></p>

    <p>
        <input type="checkbox" name="savedata" id="savedata" value="Y" checked>
        <label for="savedata"><?= Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_SAVE_DATA') ?></label>
    </p>

    <input type="submit" name="inst" value="<?= Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_BUTTON') ?>">
</form>
