<?php
/** @bxnolanginspection */

namespace Vasoft\LikeIt\Helpers;

use Vasoft\LikeIt\Entity\User;

final class UserCached extends Cached
{
    public function __construct(int $cacheTtl = 86400)
    {
        $user = User::getInstance();
        parent::__construct($cacheTtl, $user->getHash(), 'user');
    }
}
