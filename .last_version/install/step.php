<?php
/**
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}
if ($ex = $APPLICATION->GetException()) {
    CAdminMessage::ShowMessage(array(
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage("MOD_INST_ERROR"),
        'DETAILS' => $ex->GetString(),
        'HTML' => true
    ));
} else {
    CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK'));
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" value="<?= Loc::getMessage('MOD_BACK') ?>">
</form>