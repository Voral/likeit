<?php

namespace Vasoft\LikeIt\Entity;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
//use Vasoft\LikeIt\Controllers\Likes;
use Vasoft\LikeIt\Data\EO_Like;
use Vasoft\LikeIt\Data\EO_Like_Collection;
use Vasoft\LikeIt\Helpers\Cached;
use Vasoft\LikeIt\Helpers\LikeResult;
use Vasoft\LikeIt\Services\Statistic;

/**
 * Обработка лайков текущим пользователем
 * Если записи не было - добавляет лайк, если запись была - убирает лайк.
 * Лайк считается от одного пользователя если совпадает
 * хеш (от IP и UserAgent) ИЛИ идентификатор пользователя Битрикс
 */
final class Like
{
    private int $elementId;

    public function __construct(int $elementId)
    {
        echo class_exists(\Vasoft\Likeit\Controllers\Likes::class);
        $e = new \Vasoft\LikeIt\Controllers\Likes(null);
        $this->elementId = $elementId;
    }

    /**
     * Обработка лайка пользователем элемента
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function process(): int
    {
        $collection = (new Statistic())->getListUserLikesForElement($this->elementId);
        $result = LikeResult::ERROR;
        if ($collection) {
            Debug::writeToFile(__METHOD__);
            $result = $collection->count() === 0 ? $this->set() : $this->delete($collection);
            if ($result !== LikeResult::ERROR) {
                Cached::clearCacheTagged();
            }
        }
        return $result;
    }

    /**
     * Установка лайка статьи пользователем
     * @return int
     */
    private function set(): int
    {
        Debug::writeToFile(__METHOD__);
        $user = User::getInstance();
        $like = new EO_Like();
        $like
            ->setElementid($this->elementId)
            ->setHash($user->getHash())
            ->setUseragent($user->getUserAgent())
            ->setIp($user->getIP());
        if ($user->getId() > 0) {
            $like->setUserid($user->getId());
        }
        $result = $like->save();
        return $result->isSuccess() ? LikeResult::ADDED : LikeResult::ERROR;
    }

    /**
     * Удаление лайков элемента пользователем
     * на случай наличия мусора (например по ошибке оказалось несколько записей) удаляем все найденные лайки.
     * @param EO_Like_Collection $collection
     * @return int
     */
    private function delete(EO_Like_Collection $collection): int
    {
        Debug::writeToFile(__METHOD__);
        $result = true;
        foreach ($collection as $row) {
            $res = $row->delete();
            $result &= $res->isSuccess();
        }
        return $result ? LikeResult::REMOVED : LikeResult::ERROR;
    }
}