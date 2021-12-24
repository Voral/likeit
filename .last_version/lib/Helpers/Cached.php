<?php

/** @bxnolanginspection */

namespace Vasoft\LikeIt\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;

abstract class Cached
{
    private const TAGGED_CACHE = 'VasoftLikeit';
    private const CACHE_DIR = '/Vasoft/LikeIt';
    private const VERSION = '2.0';
    private string $idSuffix;
    private int $cacheTtl;
    private string $dirSuffix;

    /** @noinspection NullPointerExceptionInspection */
    public static function clearCacheTagged(): void
    {
        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag(self::TAGGED_CACHE);
    }

    public function __construct(int $cacheTtl = 86400, string $idSuffix = '', string $dirSuffix = '')
    {
        $this->idSuffix = $idSuffix;
        $this->cacheTtl = $cacheTtl;
        $this->dirSuffix = $dirSuffix;
    }

    public function get(array $data, callable $func): array
    {
        $result = [];
        $cache = null;
        try {
            $cache = Cache::createInstance();
            $cacheDir = $this->getCacheDir();
            if ($cache->initCache($this->cacheTtl, $this->getAdditionalCacheId($data), $cacheDir)) {
                $result = $cache->getVars();
            } elseif ($cache->startDataCache()) {
                $result = $func($data);
                $this->joinTags($cacheDir);
                $cache->endDataCache($result);
            }
        } catch (SystemException $e) {
            if ($cache) {
                $cache->abortDataCache();
            }
        }
        return $result;
    }

    /** @noinspection NullPointerExceptionInspection */
    public function joinTags(string $dir): void
    {
        if (defined('BX_COMP_MANAGED_CACHE')) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache($dir);
            $taggedCache->registerTag(self::TAGGED_CACHE);
            $taggedCache->endTagCache();
        }
    }

    private function getCacheDir(): string
    {
        $context = Context::getCurrent();
        $dir = $context->getSite() . self::CACHE_DIR;
        if ($this->dirSuffix !== '') {
            $dir .= ('/' . $this->dirSuffix);
        }
        return $dir;
    }

    private function getAdditionalCacheId(array $data): string
    {
        return md5(serialize([self::VERSION, $data, $this->idSuffix]));
    }

}
