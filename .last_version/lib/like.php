<?php

namespace Vasoft\Likeit;

use \Bitrix\Main\Entity;
use Bitrix\Main\Context;
use Bitrix\Main\Application;

/**
 * Class LikeTable Таблица для хранения лайков проставленных пользователями
 *
 * @package Vasoft\Likeit
 * @author Alexander Vorobyev https://va-soft.ru/
 * @version 1.2.0
 * @depricated
 */
class LikeTable extends \Vasoft\LikeIt\Data\LikeTable
{

    const LIKE_RESULT_ERROR = 0;
    const LIKE_RESULT_ADDED = 1;
    const LIKE_RESULT_REMOVED = 2;
    const COOKIE_NAME = 'VSLK_HISTORY';

    /**
     * Проверяет количество лайков для списка элементов инфоблока
     * @param array $arIDs массив ИД элементов ИБ
     * @param bool $foruser создать массив по текущему пользователю (true) или полный (false)
     * @return array
     */
    public static function checkLike(array $arIDs, $foruser = true)
    {
        $cntIds = count($arIDs);
        $arResult = [];
        if ($cntIds > 0) {
            if ($foruser) {
                $arFilterOnce = self::getFields();
                $arFilterOnce['LOGIC'] = 'OR';
            } else {
                $arFilterOnce = [];
            }
            if ($cntIds === 1) {
                $arFilter[] = $arFilterOnce;
                $arFilter['ELEMENTID'] = $arIDs[0];
                $arResult[$arIDs[0]] = 0;
            } else {
                $arFilter = ['LOGIC' => 'OR'];
                foreach ($arIDs as $id) {
                    $arFilterSub = $arFilterOnce;
                    $arFilterSub['ELEMENTID'] = $id;
                    $arFilter[] = $arFilterSub;
                    $arResult[$id] = 0;
                }
            }
            $likeIterator = self::getList([
                'filter' => $arFilter,
                'select' => ['ELEMENTID', 'CNT'],
                'group' => ['ELEMENTID'],
                'runtime' => [
                    'CNT' => [
                        'data_type' => 'integer',
                        'expression' => ['COUNT(%s)', 'ID']
                    ]
                ]
            ]);
            while ($arRecord = $likeIterator->fetch()) {
                $arResult[$arRecord['ELEMENTID']] = $arRecord['CNT'];
            }
        }
        return $arResult;
    }

    /**
     * Получение полной статистики по лайкам с информацией о выборе текущего пользователя
     * @param array $arIDS массивИД элементов ИБ
     * @return array
     */
    public static function getStatList(array $arIDS)
    {
        $arAll = self::checkLike($arIDS, false);
        $arUser = self::checkLike($arIDS);
        $arResult = [];
        foreach ($arAll as $key => $count) {
            $arResult[] = [
                'ID' => $key,
                'CNT' => $count,
                'CHECKED' => $arUser[$key]
            ];
        }
        return $arResult;
    }

    /**
     * Поучение хэша текущего поьзователя
     * @return string
     */
    public static function getHash()
    {
        $server = Context::getCurrent()->getServer();
        return md5($server->get('HTTP_USER_AGENT') . ' ' . self::getIP());
    }

    /**
     * Получение ассива общих полей
     * @return array
     */
    private static function getFields()
    {
        global $USER;
        $arResult = [];
        if ($USER->IsAuthorized()) {
            $arResult['USERID'] = $USER->GetId();
        }
        $arResult['HASH'] = self::getCookie();
        return $arResult;
    }

    /**
     * Получние значения куки текущего пользователя, если куки не существует - создается
     * @return string
     */
    public static function getCookie()
    {
        global $APPLICATION;

        $request = Context::getCurrent()->getRequest();
        $verifyCookie = trim($request->getCookie(self::COOKIE_NAME));
        if ($verifyCookie == '') {
            $verifyCookie = self::getHash();
        }
        /**
         * @todo разобраться как поставить куку D7
         * Добавление кук на D7 работает иначе. Еси выполнение прерывается,то кука на ставится.
         * Данный метод вызывается ajax.
         */
        $APPLICATION->set_cookie(self::COOKIE_NAME, $verifyCookie, time() + 60480000);
        return $verifyCookie;
    }

    /**
     * Устанваивает/снимает лайк для элемента ИБ с ИД переданным в качестве параметра
     * @param $ID ИД элемента инфоблока
     * @return int результат выпоненения:
     * - 0 - ошибка LikeTable::LIKE_RESULT_ERROR
     * - 1 - добавлен LikeTable::LIKE_RESULT_ADDED
     * - 2 - удален LikeTable::LIKE_RESULT_REMOVED
     */
    public static function setLike($ID)
    {
        $arLikes = self::checkLike([$ID]);
        $arFilter = self::getFields();
        if ($arLikes[$ID] == 0) {
            $arFilter['ELEMENTID'] = $ID;
            $server = Context::getCurrent()->getServer();
            $arFilter['IP'] = self::getIP();
            $arFilter['USERAGENT'] = $server->get('HTTP_USER_AGENT');
            $res = self::add($arFilter);
            $result = $res->isSuccess() ? self::LIKE_RESULT_ADDED : self::LIKE_RESULT_ERROR;
        } else {
            $arFilter['LOGIC'] = 'OR';
            $arFilter = [$arFilter, 'ELEMENTID' => $ID];
            $likeIterator = self::getList(['filter' => $arFilter, 'select' => ['ID']]);
            if ($likeIterator->getSelectedRowsCount() == 1) {
                $arRecord = $likeIterator->fetch();
                $res = self::delete($arRecord['ID']);
                $result = $res->isSuccess() ? self::LIKE_RESULT_REMOVED : self::LIKE_RESULT_ERROR;
            } else {
                $result = self::LIKE_RESULT_REMOVED;
            }
        }
        return $result;
    }


    private static function getIP()
    {
        $server = Context::getCurrent()->getServer();
        $ip = $server->get('HTTP_CF_CONNECTING_IP');
        if (empty($ip)) {
            $ip = $server->get('HTTP_X_REAL_IP');
        }
        return empty($ip) ? $server->get('REMOTE_ADDR') : $ip;
    }


    /**
     * Обработчик события уделения элемента инфоблока
     * @param $ID
     */
    public static function onBeforeElementDeleteHandler($ID)
    {
        $ID = intval($ID);
        if ($ID > 0) {
            $connection = Application::getInstance()->getConnection(self::getConnectionName());
            $sql = "DELETE FROM " . self::getTableName() . " WHERE ELEMENTID = %d";
            $connection->queryExecute(sprintf($sql, $ID));
        }
    }
}
