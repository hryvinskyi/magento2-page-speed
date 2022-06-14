<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\GetFileContentByUrlInterface;
use Hryvinskyi\PageSpeedApi\Api\GetStringLengthFromUrlInterface;
use Hryvinskyi\PageSpeedApi\Model\CacheInterface as PageSpeedCacheInterface;
use Magento\Framework\App\CacheInterface;

class GetStringLengthFromUrl implements GetStringLengthFromUrlInterface
{
    public const FILE_CONTENT_LENGTH_CACHE_KEY = "HRYVINSKYI_PAGESPEED_FILE_CONTENT_LENGTH";

    private CacheInterface $cache;
    private GetFileContentByUrlInterface $getFileContentByUrl;

    /**
     * @param CacheInterface $cache
     * @param GetFileContentByUrlInterface $getFileContentByUrl
     */
    public function __construct(CacheInterface $cache, GetFileContentByUrlInterface $getFileContentByUrl)
    {
        $this->cache = $cache;
        $this->getFileContentByUrl = $getFileContentByUrl;
    }

    /**
     * @inheridoc
     */
    public function execute(string $url): int
    {
        $resultFromCache = $this->cache->load(self::FILE_CONTENT_LENGTH_CACHE_KEY . '_' . $url);
        if ((int)$resultFromCache > 0) {
            return (int)$resultFromCache;
        }

        $content = $this->getFileContentByUrl->execute($url);

        $result = strlen($content);

        $this->cache->save(
            $result, self::FILE_CONTENT_LENGTH_CACHE_KEY . '_' . $url,
            [PageSpeedCacheInterface::CACHE_TAG]
        );

        return $result;
    }
}
