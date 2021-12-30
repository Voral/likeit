<?php

namespace Vasoft\LikeIt\Services;

//use Bitrix\Iblock\ORM\Query;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use Vasoft\LikeIt\Data\EO_Like_Collection;
use Vasoft\LikeIt\Data\EO_Like_Query;
use Vasoft\LikeIt\Data\LikeTable;
use Vasoft\LikeIt\Entity\User;
use Vasoft\LikeIt\Helpers\TotalCached;
use Vasoft\LikeIt\Helpers\UserCached;

final class Statistic
{
    public function __construct()
    {
    }

    public function get(array $ids): array
    {
        $arAll = $this->checkLikeCached($ids);
        $arUser = $this->checkLikeUserCached($ids);
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

    public function checkLikeCached($ids): array
    {
        return (new TotalCached())->get($ids, [$this, 'checkLike']);
    }

    public function checkLikeUserCached($ids): array
    {
        return (new UserCached())->get($ids, [$this, 'checkLikeUser']);
    }

    /**
     * Проверяет количество лайков для списка элементов инфоблока
     * @param array $arIDs
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    public function checkLike(array $arIDs): array
    {
        $arResult = [];
        foreach ($arIDs as $id) {
            $arResult[$id] = 0;
        }
        $query = $this->generateQuery($arIDs);
        if ($query) {
            $this->makeQueryGrouped($query);
            $iterator = $query->exec();
            foreach ($iterator as $row) {
                $arResult[(int)$row['ELEMENTID']] = (int)$row['CNT'];
            }
        }
        return $arResult;
    }

    /**
     * Проверяет количество лайков для списка элементов инфоблока для текущего пользователя
     * @param array $arIDs
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function checkLikeUser(array $arIDs): array
    {
        $arResult = [];
        foreach ($arIDs as $id) {
            $arResult[$id] = 0;
        }
        $query = $this->generateQueryForUser($arIDs);
        if ($query) {
            $this->makeQueryGrouped($query);
            $iterator = $query->exec();
            foreach ($iterator as $row) {
                $arResult[(int)$row['ELEMENTID']] = (int)$row['CNT'];
            }
        }
        return $arResult;
    }

    /**
     * @param array $arIDs
     * @return EO_Like_Query|null|Query
     * @throws ArgumentException
     * @throws SystemException
     */
    private function makeQueryGrouped(Query $query): void
    {
        $query
            ->addGroup('ELEMENTID')
            ->addSelect(new Fields\ExpressionField('CNT', 'COUNT(ID)'));
    }


    /**
     * @param array $arIDs
     * @return EO_Like_Query|null|Query
     * @throws ArgumentException
     * @throws SystemException
     */
    private function generateQuery(array $arIDs): ?Query
    {
        $countElements = count($arIDs);
        if ($countElements === 0) {
            return null;
        }
        $query = LikeTable::query();
        if ($countElements === 1) {
            $query->where('ELEMENTID', $arIDs[0]);
        } else {
            $query->whereIn('ELEMENTID', $arIDs);
        }
        $query
            ->addSelect('ELEMENTID');
        return $query;
    }

    /**
     * @param array $arIDs
     * @return EO_Like_Query|null|Query
     * @throws ArgumentException
     * @throws SystemException
     */
    private function generateQueryForUser(array $arIDs): ?Query
    {
        $query = $this->generateQuery($arIDs);
        if (!$query) {
            return null;
        }
        $user = User::getInstance();
        $userFilter = Query::filter()
            ->logic(Query::filter()::LOGIC_OR)
            ->where('HASH', $user->getHash());
        if ($user->getId() > 0) {
            $userFilter
                ->where('USERID', $user->getId());
        }
        $query->where($userFilter);
        return $query;
    }

    /**
     * Получение списка лайков одной статьи одним пользователем
     * @param int $elementId
     * @return EO_Like_Collection|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getListUserLikesForElement(int $elementId): ?EO_Like_Collection
    {
        $query = $this->generateQueryForUser([$elementId]);
        if (!$query) {
            return null;
        }
        $query->addSelect('ID');
        return $query->fetchCollection();
    }
}