<?php
/**
 * Успешное удаление модуля
 */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}

Loc::loadMessages(__FILE__);
?>

<div class="adm-info-message-wrap adm-info-message-green">
    <div class="adm-info-message">
        <div class="adm-info-message-title"><?= Loc::getMessage('KIRSYP_CRM_LOGGING_UNINSTALL_SUCCESS') ?></div>
        <div class="adm-info-message-icon"></div>
    </div>
</div>
