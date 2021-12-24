<?php
/**
 * Файл будет удален - отключаем проверки
 * @noinspection PhpUnusedPrivateMethodInspection
 * @noinspection PhpUnused
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection AccessModifierPresentedInspection
 * @noinspection AutoloadingIssuesInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection ReturnTypeCanBeDeclaredInspection
 */

namespace Vasoft\Likeit;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Vasoft\LikeIt\Entity\Like;
use Vasoft\LikeIt\Entity\User;
use Vasoft\LikeIt\Services\Statistic;

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
     * @depricated
     * @param array $arIDs
     * @param $foruser
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function checkLike(array $arIDs, $foruser = true)
    {
        $stat = new Statistic();
        $result = $foruser ? $stat->checkLikeUser($arIDs) : $stat->checkLike($arIDs);
        self::flushCookie();
        return $result;
    }

    /**
     * @depricated
     */
    public static function getStatList(array $arIDS)
    {
        $result = (new Statistic())->get($arIDS);
        self::flushCookie();
        return $result;
    }

    /**
     * @depricated
     * @return string
     */
    public static function getHash()
    {
        $result = User::getInstance()->getHash();
        self::flushCookie();
        return $result;
    }

    /**
     * @return array
     * @deprecated
     */
    private static function getFields()
    {
        $user = User::getInstance();
        $arResult = [];
        if ($user->getId() > 0) {
            $arResult['USERID'] = $user->getId();
        }
        $arResult['HASH'] = $user->getHash();
        self::flushCookie();
        return $arResult;
    }

    /**
     * @depricated
     * @return string
     */
    public static function getCookie()
    {
        $verifyCookie = User::getInstance()->getHash();
        self::flushCookie();
        return $verifyCookie;
    }

    private static function flushCookie(): void
    {
        \CMain::FinalActions();
    }

    /**
     * @param int $ID ИД элемента инфоблока
     * @return int Результат выполнения:
     * - 0 - ошибка LikeResult::LIKE_RESULT_ERROR
     * - 1 - добавлен LikeResult::LIKE_RESULT_ADDED
     * - 2 - удален LikeResult::LIKE_RESULT_REMOVED
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @deprecated
     * Устанавливает/снимает лайк для элемента ИБ с ИД переданным в качестве параметра
     */
    public static function setLike($ID)
    {
        return (new Like((int)$ID))->process();
    }

    /**
     * @deprecated
     */
    private static function getIP()
    {
        $server = Context::getCurrent()->getServer();
        $ip = $server->get('HTTP_CF_CONNECTING_IP');
        if (empty($ip)) {
            $ip = $server->get('HTTP_X_REAL_IP');
        }
        return empty($ip) ? $server->get('REMOTE_ADDR') : $ip;
    }
}
