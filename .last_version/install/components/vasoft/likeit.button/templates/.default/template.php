<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

$this->setFrameMode(true);
if ($arParams['ID'] > 0):
    ?>
	<span class="vs-likeit<?php
    if ($arParams['ENABLE_ACTION'] === 'Y') echo '  vs-likeit-action';
    ?>" dataid="<?= $arParams['ID'] ?>"><?php
        if ($arParams['SHOW_COUNTER'] === 'Y'):
            ?><span class="vs-likeit-cnt"></span><? endif;
        ?></span>
<?php
endif;