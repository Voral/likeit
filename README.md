# Модуль 1C-Bitrix CMS лайки элементов информационных блоков

ИД модуля: vasoft.likeit

## Возможности

Модуль обеспечивает обработку "Лайков" проставляемых посетителями сайта для элементов информационных блоков. При первом
клике по кнопке отмеченной для модуля происходит установка лайка, при повторном - отмена.

## Ограничения

- Bitrix версии 21.600 или выше
- PHP версии 7.4 или выше

## Установка

- Установите модуль стандартным способом
- Подключите компонент:

```php
$APPLICATION->IncludeComponent(
    "vasoft:likeit.button",
    ".default",
    array(
        "SHOW_COUNTER" => "Y", // отображать счетчик
        "ENABLE_ACTION" => "Y", // разрешить голосование
        "ID" => $arResult['ID'] // идентификатор элемента
    ),
    false
);
```

Либо выполнить следующее:

- Указать элемент или элементы, которые будут содержать информацию о лайках. Для этого необходимо указать css-класс '
  vs-likeit' и добавить атрибут 'dataid' со значением ИД элемента информационного блока
- Для элементов, которые так же являются кнопками установки/отмены "лайка", указать css-класс vs-likeit-action
- для отображения количества установленных "лайков" разместить внутри элемента с классом vs-likeit элемент с классом
  vs-likeit-cnt
- подключить скрипт (c учетом кеширования)
  
Вне кешируемой области:

```php
use Bitrix\Main\Page\Asset;
Asset::getInstance()->addJs('/bitrix/js/vasoft.likeit/likeit.js');
```

Внутри шаблонов омпонентов

```php
$this->addExternalJS('/bitrix/js/vasoft.likeit/likeit.js');
```

Пример элементов:

```html
<span class="vs-likeit" dataid="10"><span class="vs-likeit-cnt"></span></span>
<span class="vs-likeit vs-likeit-action" dataid="10"><span class="vs-likeit-cnt"></span></span>
<span class="vs-likeit vs-likeit-action" dataid="10"></span>
```

Если соответствующий элемент информационного блока уже был "лайкнут" текущим пользователем - элементу HTML добавляется
класс 'vs-likeit-active'.

Класс 'vs-likeit-action' указывается если необходимо обрабатывать клик.

Классы 'vs-likeit-active' и 'vs-likeit-cnt' можно переопределить задавая значения JavaScript переменным

```js
window.vas_likeit_classactive = 'my-acive';
window.vas_likeit_classcnt = 'my-cnt';
```

Так же получить статистику по лайкам в шаблонах при помощи команды (где $arIDs - массив ИД элементов инфо-блока)

```php
\Bitrix\Main\Loader::includeModule('vasoft.likeit');
$arIDs = [12334, 12334];
$stat = new \Vasoft\LikeIt\Services\Statistic(); 
// Без учета текущего пользователя и без кеширования
$arLikes = $stat->checkLike($arIDs);
// Без учета текущего пользователя и с кешированием
$arLikes = $stat->checkLikeCached($arIDs);
// С информацией о выборе текущего пользователя и без кеширования
$arLikes = $stat->checkLikeUser($arIDs);
// С информацией о выборе текущего пользователя и с кешированием
$arLikes = $stat->checkLikeUserCached($arIDs);
```

## Дополнительная информация

- [Страница модуля](https://va-soft.ru/market/likeit/)
- [Страница компонента кнопки](https://va-soft.ru/docs/likeit-button/)
- [Модуль на Маркетплейс 1С-Битрикс](https://marketplace.1c-bitrix.ru/solutions/vasoft.likeit/)
