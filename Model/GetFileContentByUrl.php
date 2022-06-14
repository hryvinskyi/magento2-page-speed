<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\GetFileContentByUrlInterface;
use Hryvinskyi\PageSpeedApi\Api\GetLocalPathFromUrlInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManager;

class GetFileContentByUrl implements GetFileContentByUrlInterface
{
    private GetLocalPathFromUrlInterface $getLocalPathFromUrl;
    private EventManager $eventManager;

    /**
     * @param GetLocalPathFromUrlInterface $getLocalPathFromUrl
     * @param EventManager $eventManager
     */
    public function __construct(
        GetLocalPathFromUrlInterface $getLocalPathFromUrl,
        EventManager $eventManager
    ) {
        $this->getLocalPathFromUrl = $getLocalPathFromUrl;
        $this->eventManager = $eventManager;
    }

    /**
     * @inheridoc
     */
    public function execute(string $url): string
    {
        $localPath = $this->getLocalPathFromUrl->execute($url);
        $content = '';

        if (file_exists($localPath)) {
            $content = file_get_contents($localPath);
        }

        if (empty($content)) {
            $content = @file_get_contents($url);
        }

        $content = is_bool($content) ? '' : $content;
        $data = new DataObject();
        $data->setData('content', $content);
        $data->setData('file', $url);
        $this->eventManager->dispatch('pagespeed_prepare_content_on_merge_files', ['data' => $data]);

        return $data->getData('content');
    }
}
