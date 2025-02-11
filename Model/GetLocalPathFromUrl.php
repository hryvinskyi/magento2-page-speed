<?php
/**
 * Copyright (c) 2025. All rights reserved.
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
    private array $cache = [];

    public const URL_TYPES = [
        UrlInterface::URL_TYPE_MEDIA => DirectoryList::MEDIA,
        UrlInterface::URL_TYPE_STATIC => DirectoryList::STATIC_VIEW,
        UrlInterface::URL_TYPE_WEB => DirectoryList::ROOT
    ];

    public function __construct(StoreManagerInterface $storeManager, Filesystem $filesystem)
    {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
    }

    /**
     * Removes the static version from the given URL.
     *
     * @param string $url The URL from which to remove the static version.
     *
     * @return string The URL without the static version.
     */
    private function removeStatic(string $url): string
    {
        $pattern = '/\/static\/version\d+\//';
        return preg_replace($pattern, '/static/', $url);
    }

    /**
     * Retrieves a list of base URLs along with their corresponding absolute paths for media files.
     *
     * @return array An array of base URLs and absolute paths.
     */
    private function getBaseUrlList(): array
    {
        $baseUrlList = [];

        foreach (self::URL_TYPES as $urlType => $directoryType) {
            $baseUrlList[] = [
                $this->storeManager->getStore()->getBaseUrl($urlType),
                $this->filesystem->getDirectoryRead($directoryType)->getAbsolutePath()
            ];
        }

        return $baseUrlList;
    }

    /**
     * Processes the base URL list and replaces the base URL in the given URL with the corresponding replacement URL.
     *
     * @param string $url The URL to process.
     *
     * @return string The modified URL with the base URL replaced.
     */
    private function processBaseUrlList(string $url): string
    {
        foreach ($this->getBaseUrlList() as $baseUrlData) {
            if (strpos($url, $this->removeStatic($baseUrlData[0])) === 0) {
                return str_replace($this->removeStatic($baseUrlData[0]), $this->removeStatic($baseUrlData[1]), $url);
            }
        }

        return $url;
    }

    /**
     * Removes the fragment from the given URL.
     *
     * @param string $url The URL from which to remove the fragment.
     *
     * @return string The URL without the fragment.
     */
    private function removeFragment(string $url): string
    {
        if ($fragment = parse_url($url, PHP_URL_FRAGMENT)) {
            $url = str_replace('#' . $fragment, '', $url);
        }

        return $url;
    }

    /**
     * Removes the query string from the given URL and returns the modified URL.
     *
     * @param string $url The URL to be processed.
     *
     * @return string The modified URL.
     */
    private function removeQuery(string $url): string
    {
        if ($query = parse_url($url, PHP_URL_QUERY)) {
            $url = str_replace('?' . $query, '', $url);
        }

        return rtrim($url, '?#');
    }

    /**
     * Executes a series of operations on the given URL and returns the modified URL.
     *
     * @param string $url The URL to be processed.
     *
     * @return string The modified URL.
     */
    public function execute(string $url): string
    {
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        $url = $this->removeStatic($url);
        $url = $this->processBaseUrlList($url);
        $url = $this->removeFragment($url);
        $this->cache[$url] = $this->removeQuery($url);

        return $this->cache[$url];
    }
}
