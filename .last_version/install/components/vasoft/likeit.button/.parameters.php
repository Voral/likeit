<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = array(
    "PARAMETERS" => array(
        "SHOW_COUNTER" => array(
            "NAME" => Loc::getMessage("VASOFT_LIKEIT.SHOW_COUNTER"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "ADDITIONAL_VALUES" => "N",
            "PARENT" => "BASE",
        ),
        "ENABLE_ACTION" => array(
            "NAME" => Loc::getMessage("VASOFT_LIKEIT.ENABLE_ACTION"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "ADDITIONAL_VALUES" => "N",
            "PARENT" => "BASE",
        ),
        "ID" => array(
            "NAME" => Loc::getMessage("VASOFT_LIKEIT.ID"),
            "TYPE" => "STRING",
            "DEFAULT" => "={\$arResult['ID']}",
            "PARENT" => "BASE",
        )
    )
);
