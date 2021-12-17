<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Page\Asset;

Loc::loadLanguageFile(__FILE__);

class VasoftLikeitButtonComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams["ID"] = isset($arParams["ID"]) ? (int)$arParams["ID"] : 0;
        $arParams["SHOW_COUNTER"] = (isset($arParams["SHOW_COUNTER"]) && $arParams["SHOW_COUNTER"] === 'Y') ? 'Y' : 'N';
        $arParams["ENABLE_ACTION"] = (isset($arParams["ENABLE_ACTION"]) && $arParams["ENABLE_ACTION"] === 'Y') ? 'Y' : 'N';
        return $arParams;
    }

    /**
     * Выполнение компонента
     */
    public function executeComponent()
    {
        $this->includeComponentTemplate();
        $this->getTemplate()->addExternalJs('/bitrix/js/vasoft.likeit/likeit.js');
    }

}
