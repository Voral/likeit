<?php
/** @bxnolanginspection */

namespace Vasoft\LikeIt\Helpers;

final class TotalCached extends Cached
{
    public function __construct(int $cacheTtl = 86400)
    {
        parent::__construct($cacheTtl, '','total');
    }
}
