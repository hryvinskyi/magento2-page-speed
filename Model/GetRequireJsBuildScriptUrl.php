<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\GetRequireJsBuildScriptUrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class GetRequireJsBuildScriptUrl implements GetRequireJsBuildScriptUrlInterface
{
    private AssetRepository $assetRepository;

    /**
     * @param AssetRepository $assetRepository
     */
    public function __construct(AssetRepository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    /**
     * @inheridoc
     */
    public function execute(string $url): string
    {
        $jsBuildScript = $this->assetRepository->getUrl(self::LIB_JS_BUILD_SCRIPT);
        if (strpos($jsBuildScript, '/_view/') === false) {
            return $jsBuildScript;
        }
        $pos = strpos($jsBuildScript, DIRECTORY_SEPARATOR . '_view' . DIRECTORY_SEPARATOR);
        $path = substr($url, $pos + 1);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $theme = $parts[0] . DIRECTORY_SEPARATOR . $parts[1];

        return str_replace(DIRECTORY_SEPARATOR . '_view' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR, $jsBuildScript);
    }
}
