<?php

use Bitrix\Main\Localization\Loc;

/**
 *
 * @author Воробьев Александр
 * @version 1.0.0
 * @package vasoft.cookie
 * @subpackage
 *
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = array(
    "NAME" => Loc::getMessage("VASOFT_LIKEIT.NAME"),
    "DESCRIPTION" => Loc::getMessage("VASOFT_LIKEIT.DESCRIPTION"),
    "SORT" => 20,
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => ""
    ),
    "COMPLEX" => "N",
);
