<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\GetLocalPathFromUrlInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetLocalPathFromUrl implements GetLocalPathFromUrlInterface
{
    private StoreManagerInterface $storeManager;
    private Filesystem $filesystem;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     */
    public function __construct(StoreManagerInterface $storeManager, Filesystem $filesystem)
    {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheridoc
     */
    public function execute(string $url): string
    {
        $baseUrlList = [
            [
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath()
            ],
            [
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA, true),
                $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath()
            ],
            [
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC),
                $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath()
            ],
            [
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC, true),
                $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath()
            ],
            [
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB),
                $this->filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath()
            ],
            [
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB, true),
                $this->filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath()
            ],
        ];

        foreach ($baseUrlList as $baseUrlData) {
            if (strpos($url, $baseUrlData[0]) === 0) {
                $url = str_replace($baseUrlData[0], $baseUrlData[1], $url);
                break;
            }
        }

        if ($fragment = parse_url($url, PHP_URL_FRAGMENT)) {
            $url = str_replace('#' . $fragment, '', $url);
        }

        if ($query = parse_url($url, PHP_URL_QUERY)) {
            $url = str_replace('?' . $query, '', $url);
        }

        return rtrim($url, '?#');
    }
}
