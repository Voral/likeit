<?php

/**
 * Файл будет исключен из следующих версий - будут работать контроллеры модуля
 * @depricated
 * module: vasoft.likeit
 *
 * @global CMain $APPLICATION
 *
 * @noinspection DuplicatedCode
 * @noinspection PhpDefineCanBeReplacedWithConstInspection
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection JsonEncodingApiUsageInspection
 * @noinspection PhpComposerExtensionStubsInspection
 */

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Vasoft\LikeIt\Services\Statistic;

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php');

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$arIDs = $request->get('IDS');
$arResult = ['RESULT' => 0, 'ITEMS' => []];

$APPLICATION->RestartBuffer();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

if (Loader::includeModule('vasoft.likeit')) {
    $stat = new Statistic();
    $arResult['ITEMS'] = $stat->get($arIDs);
    $arResult['RESULT'] = 1;
}
echo json_encode($arResult);
die();
