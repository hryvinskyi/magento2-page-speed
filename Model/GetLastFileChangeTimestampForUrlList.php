<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\GetLastFileChangeTimestampForUrlListInterface;
use Hryvinskyi\PageSpeedApi\Api\GetLocalPathFromUrlInterface;

class GetLastFileChangeTimestampForUrlList implements GetLastFileChangeTimestampForUrlListInterface
{
    private GetLocalPathFromUrlInterface $getLocalPathFromUrl;

    /**
     * @param GetLocalPathFromUrlInterface $getLocalPathFromUrl
     */
    public function __construct(GetLocalPathFromUrlInterface $getLocalPathFromUrl)
    {
        $this->getLocalPathFromUrl = $getLocalPathFromUrl;
    }

    /**
     * @inheridoc
     */
    public function execute(array $urls): int
    {
        $timestampList = 0;

        foreach ($urls as $url) {
            $filePath = $this->getLocalPathFromUrl->execute($url);
            $timestampList += (int)@filemtime($filePath);
        }

        return $timestampList;
    }
}
