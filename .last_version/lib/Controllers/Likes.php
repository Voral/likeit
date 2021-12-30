<?php
/** @bxnolanginspection */

namespace Vasoft\LikeIt\Controllers;

use Bitrix\Main\Error;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Vasoft\LikeIt\Entity\Like;
use Vasoft\LikeIt\Helpers\LikeResult;
use Vasoft\LikeIt\Services\Statistic;

class Likes extends Controller
{
    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function configureActions()
    {
        $arDefault = ['prefilters' => [new ActionFilter\Csrf()]];
        return [
            'like' => $arDefault,
            'list' => $arDefault,
        ];
    }

    public function listAction(array $ids = []): ?array
    {
        $stat = new Statistic();
        return ['ITEMS' => $stat->get($ids)];
    }

    /**
     * @param int $id
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function likeAction(int $id): ?array
    {
        $result = (new Like($id))->process();
        if ($result === LikeResult::ERROR) {
            $this->errorCollection[] = new Error('Like processing error');
        }
        return ['result' => $result, 'id' => $id];
    }
}
