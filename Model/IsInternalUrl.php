<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\IsInternalUrlInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class IsInternalUrl implements IsInternalUrlInterface
{
    private StoreManagerInterface $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @inheridoc
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $url): bool
    {
        $isUrl = false;
        $isUrl = $isUrl || strpos($url, 'http://') === 0;
        $isUrl = $isUrl || strpos($url, 'https://') === 0;
        $isUrl = $isUrl || strpos($url, '//') === 0;

        if (!$isUrl) {
            return false;
        }

        return $this->isLocalWebUrl($url) || $this->isLocalMediaUrl($url) || $this->isLocalStaticUrl($url);
    }

    /**
     * @param string $url
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isLocalWebUrl(string $url): bool
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $secureBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);

        return strpos($url, $baseUrl) === 0 || strpos($url, $secureBaseUrl) === 0;
    }

    /**
     * @param string $url
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isLocalMediaUrl(string $url): bool
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $secureBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA, true);
        return strpos($url, $baseUrl) === 0 || strpos($url, $secureBaseUrl) === 0;
    }

    /**
     * @param string $url
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isLocalStaticUrl(string $url): bool
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        $secureBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC, true);
        return strpos($url, $baseUrl) === 0 || strpos($url, $secureBaseUrl) === 0;
    }
}
