<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\PutContentInFileInterface;
use Hryvinskyi\PageSpeedJsMerge\Api\ConfigInterface;

class PutContentInFile implements PutContentInFileInterface
{
    private ConfigInterface $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $content, string $filePath): void
    {
        $path = str_replace(BP . DIRECTORY_SEPARATOR, '', $filePath);
        $pathToTarget = BP;
        $pathMap = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($pathMap as $key => $pathPart) {
            $pathToTarget .= DIRECTORY_SEPARATOR . $pathPart;

            if (file_exists($pathToTarget) && is_dir($pathToTarget)) {
                continue;
            }

            if ($key === (count($pathMap) - 1)) {
                $result = file_put_contents($pathToTarget, $content, LOCK_EX);
                if ($result === false) {
                    throw new \RuntimeException('Unable to put content in file: ' . $pathToTarget);
                }
                @chmod($pathToTarget, $this->config->getFilePermission());
                break;
            }

            if (!mkdir($pathToTarget, $this->config->getFolderPermission()) && !is_dir($pathToTarget)) {
                throw new \RuntimeException('Unable to create directory: ' . $pathToTarget);
            }
        }
    }
}
