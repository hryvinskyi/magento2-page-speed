<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Model\CacheInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Cache implements CacheInterface
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
     * @param bool $isSecure
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRootCacheUrl(bool $isSecure = false): string
    {
        return rtrim($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC, $isSecure), '/')
            . DIRECTORY_SEPARATOR . self::MAIN_FOLDER;
    }

    public function getRootCachePath(): string
    {
        return rtrim($this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . self::MAIN_FOLDER;
    }
}
