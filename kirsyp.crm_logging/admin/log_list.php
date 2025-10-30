<?php
/**
 * Страница просмотра логов
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Kirsyp\CrmLogging\LogTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('KIRSYP_CRM_LOGGING_PAGE_TITLE'));

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

$request = Application::getInstance()->getContext()->getRequest();

// Параметры фильтрации
$filterUserId = $request->get('filter_user_id');
$filterDateFrom = $request->get('filter_date_from');
$filterDateTo = $request->get('filter_date_to');
$filterEntityId = $request->get('filter_entity_id');

// Параметры пагинации
$pageSize = 50;
$page = (int) $request->get('page') ?: 1;
$offset = ($page - 1) * $pageSize;

// Фильтр
$filter = [];

if ($filterUserId) {
    $filter['=UF_USER_ID'] = $filterUserId;
}

if ($filterEntityId) {
    $filter['=UF_ENTITY_ID'] = $filterEntityId;
}

if ($filterDateFrom) {
    $filter['>=UF_DATE'] = new \Bitrix\Main\Type\DateTime($filterDateFrom);
}

if ($filterDateTo) {
    $filter['<=UF_DATE'] = new \Bitrix\Main\Type\DateTime($filterDateTo . ' 23:59:59');
}

// Получаем данные
$items = LogTable::getList($filter, ['ID' => 'DESC'], $pageSize, $offset);
$totalCount = LogTable::getCount($filter);

// Пагинация
$totalPages = ceil($totalCount / $pageSize);

?>

<div class="adm-toolbar-panel-container">
    <form name="form_filter" method="get" action="<?= $APPLICATION->GetCurPage() ?>">
        <table class="adm-filter" width="100%">
            <tr class="adm-filter-title">
                <td colspan="2">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_TITLE') ?>
                </td>
            </tr>
            <tr>
                <td class="adm-filter-param-name" width="30%">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_USER') ?>:
                </td>
                <td>
                    <input type="text" name="filter_user_id" value="<?= htmlspecialcharsbx($filterUserId) ?>" size="30">
                </td>
            </tr>
            <tr>
                <td class="adm-filter-param-name">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_ENTITY') ?>:
                </td>
                <td>
                    <select name="filter_entity_id">
                        <option value=""><?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_ENTITY_ALL') ?></option>
                        <option value="CRM_LEAD" <?= $filterEntityId == 'CRM_LEAD' ? 'selected' : '' ?>>
                            <?= Loc::getMessage('KIRSYP_CRM_LOGGING_ENTITY_LEAD') ?>
                        </option>
                        <option value="CRM_DEAL" <?= $filterEntityId == 'CRM_DEAL' ? 'selected' : '' ?>>
                            <?= Loc::getMessage('KIRSYP_CRM_LOGGING_ENTITY_DEAL') ?>
                        </option>
                        <option value="CRM_COMPANY" <?= $filterEntityId == 'CRM_COMPANY' ? 'selected' : '' ?>>
                            <?= Loc::getMessage('KIRSYP_CRM_LOGGING_ENTITY_COMPANY') ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-filter-param-name">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_DATE_FROM') ?>:
                </td>
                <td>
                    <?= \CAdminCalendar::CalendarDate('filter_date_from', $filterDateFrom, 10, true) ?>
                </td>
            </tr>
            <tr>
                <td class="adm-filter-param-name">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_DATE_TO') ?>:
                </td>
                <td>
                    <?= \CAdminCalendar::CalendarDate('filter_date_to', $filterDateTo, 10, true) ?>
                </td>
            </tr>
            <tr class="adm-filter-buttons">
                <td colspan="2">
                    <input type="submit" name="set_filter"
                        value="<?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_BUTTON') ?>" class="adm-btn">
                    <input type="button" name="del_filter"
                        value="<?= Loc::getMessage('KIRSYP_CRM_LOGGING_FILTER_RESET') ?>"
                        onclick="window.location.href='<?= $APPLICATION->GetCurPage() ?>'" class="adm-btn">
                </td>
            </tr>
        </table>
    </form>
</div>

<?php if ($totalCount > 0): ?>

    <table class="adm-list-table" width="100%">
        <thead>
            <tr class="adm-list-table-header">
                <td class="adm-list-table-cell" width="5%" style="text-align: center;">ID</td>
                <td class="adm-list-table-cell" width="15%" style="text-align: center;">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_COL_USER') ?></td>
                <td class="adm-list-table-cell" width="10%" style="text-align: center;">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_COL_ENTITY') ?></td>
                <td class="adm-list-table-cell" width="10%" style="text-align: center;">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_COL_ELEMENT_ID') ?></td>
                <td class="adm-list-table-cell" width="40%" style="text-align: center;">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_COL_CHANGES') ?></td>
                <td class="adm-list-table-cell" width="20%" style="text-align: center;">
                    <?= Loc::getMessage('KIRSYP_CRM_LOGGING_COL_DATE') ?></td>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $changes = json_decode($item['UF_CHANGE_LOG'], true);
                $user = CUser::GetByID($item['UF_USER_ID'])->Fetch();
                ?>
                <tr class="adm-list-table-row">
                    <td class="adm-list-table-cell"><?= $item['ID'] ?></td>
                    <td class="adm-list-table-cell">
                        <?= $user ? htmlspecialcharsbx($user['NAME'] . ' ' . $user['LAST_NAME']) : $item['UF_USER_ID'] ?>
                    </td>
                    <td class="adm-list-table-cell">
                        <?php
                        switch ($item['UF_ENTITY_ID']) {
                            case 'CRM_LEAD':
                                echo Loc::getMessage('KIRSYP_CRM_LOGGING_ENTITY_LEAD');
                                break;
                            case 'CRM_DEAL':
                                echo Loc::getMessage('KIRSYP_CRM_LOGGING_ENTITY_DEAL');
                                break;
                            case 'CRM_COMPANY':
                                echo Loc::getMessage('KIRSYP_CRM_LOGGING_ENTITY_COMPANY');
                                break;
                            default:
                                echo htmlspecialcharsbx($item['UF_ENTITY_ID']);
                        }
                        ?>
                    </td>
                    <td class="adm-list-table-cell">
                        <a href="/crm/<?= strtolower(str_replace('CRM_', '', $item['UF_ENTITY_ID'])) ?>/details/<?= $item['UF_ELEMENT_ID'] ?>/"
                            target="_blank">
                            <?= $item['UF_ELEMENT_ID'] ?>
                        </a>
                    </td>
                    <td class="adm-list-table-cell">
                        <?php if (is_array($changes)): ?>
                            <div style="max-height: 150px; overflow-y: auto;">
                                <table width="100%" style="font-size: 11px;">
                                    <?php foreach ($changes as $field => $change): ?>
                                        <tr>
                                            <td style="border-bottom: 1px solid #e0e0e0;">
                                                <strong><?= htmlspecialcharsbx($field) ?>:</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px; border-bottom: 1px solid #e0e0e0;">
                                                <span style="color: #888;"><?=Loc::getMessage('KIRSYP_CRM_OLD_VALUE')?></span>
                                                <?= htmlspecialcharsbx(mb_substr($change['old'], 0, 100)) ?>
                                                <?= mb_strlen($change['old']) > 100 ? '...' : '' ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px; border-bottom: 1px solid #e0e0e0; margin-bottom: 5px;">
                                                <span style="color: #080;"><?=Loc::getMessage('KIRSYP_CRM_NEW_VALUE')?></span>
                                                <?= htmlspecialcharsbx(mb_substr($change['new'], 0, 100)) ?>
                                                <?= mb_strlen($change['new']) > 100 ? '...' : '' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="adm-list-table-cell">
                        <?= $item['UF_DATE'] ? $item['UF_DATE']->toString() : '' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a
                        href="?page=<?= $i ?><?= $filterUserId ? '&filter_user_id=' . urlencode($filterUserId) : '' ?><?= $filterDateFrom ? '&filter_date_from=' . urlencode($filterDateFrom) : '' ?><?= $filterDateTo ? '&filter_date_to=' . urlencode($filterDateTo) : '' ?><?= $filterEntityId ? '&filter_entity_id=' . urlencode($filterEntityId) : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 10px;">
        <?= Loc::getMessage('KIRSYP_CRM_LOGGING_TOTAL') ?>: <?= $totalCount ?>
    </div>

<?php else: ?>
    <div class="adm-info-message">
        <?= Loc::getMessage('KIRSYP_CRM_LOGGING_NO_DATA') ?>
    </div>
<?php endif; ?>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'); ?>