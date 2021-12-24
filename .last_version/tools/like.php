<?php

/**
 * Файл будет исключен из следующих версий - будут работать контроллеры модуля
 * @depricated
 * @module: vasoft.likeit
 *
 * @noinspection PhpDefineCanBeReplacedWithConstInspection
 * @noinspection JsonEncodingApiUsageInspection
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection DuplicatedCode
 */

use Bitrix\Main\Application;
use Vasoft\Likeit\LikeTable;

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php');

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$ID = (int)$request->get('ID');
$arResult = ['RESULT' => 0];

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');


if ($ID > 0 && \Bitrix\Main\Loader::includeModule('vasoft.likeit')) {
    $arResult['RESULT'] = LikeTable::setLike($ID);
    $arResult['ID'] = $ID;
}
echo json_encode($arResult);
die();
