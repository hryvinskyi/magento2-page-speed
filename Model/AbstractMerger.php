<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface;
use Hryvinskyi\PageSpeedApi\Api\GetLocalPathFromUrlInterface;
use Hryvinskyi\PageSpeedApi\Model\MergerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractMerger implements MergerInterface
{
    private GetLocalPathFromUrlInterface $getLocalPathFromUrl;
    private LoggerInterface $logger;

    /**
     * @param GetLocalPathFromUrlInterface $getLocalPathFromUrl
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetLocalPathFromUrlInterface $getLocalPathFromUrl,
        LoggerInterface $logger
    ) {
        $this->getLocalPathFromUrl = $getLocalPathFromUrl;
        $this->logger = $logger;
    }

    /**
     * @inheridoc
     */
    public function merge(array $list): ?string
    {
        $localPathList = [];
        foreach ($list as $tag) {
            $localPathList[] = $this->getLocalPath($this->getPathFromTag($tag));
        }
        return $this->mergeFileList($localPathList);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function getLocalPath(string $url): string
    {
        return $this->getLocalPathFromUrl->execute($url);
    }


    /**
     * @param string[] $files
     *
     * @return string|null
     */
    abstract public function mergeFileList(array $files): ?string;

    /**
     * @param RawInterface $tag
     *
     * @return string
     */
    abstract public function getPathFromTag(RawInterface $tag): string;

    /**
     * @param array $srcFiles
     * @param string $targetFile
     * @param bool $mustMerge
     * @param \Closure|null $beforeMergeCallback
     * @param array $extensionsFilter
     * @return mixed
     */
    public function mergeFiles(
        array $srcFiles,
        string $targetFile,
        bool $mustMerge = false,
        \Closure $beforeMergeCallback = null,
        array $extensionsFilter = []
    ) {
        try {
            $shouldMerge = $mustMerge || $targetFile !== '';

            if ($shouldMerge !== false) {
                if (!file_exists($targetFile)) {
                    $shouldMerge = true;
                } else {
                    $targetMtime = filemtime($targetFile);
                    foreach ($srcFiles as $file) {
                        if (!file_exists($file) || @filemtime($file) > $targetMtime) {
                            $shouldMerge = true;
                            break;
                        }
                    }
                }
            }
            if ($shouldMerge === true) {
                if ($targetFile && !is_writable(dirname($targetFile))) {
                    throw new \Exception(sprintf('Path %s is not writable.', dirname($targetFile)));
                }

                if (!empty($srcFiles)) {
                    foreach ($srcFiles as $key => $file) {
                        $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($fileExt, $extensionsFilter, true) === false) {
                            unset($srcFiles[$key]);
                        }
                    }
                }

                if (count($srcFiles) === 0) {
                    throw new \Exception('No files to compile.');
                }

                $data = '';
                foreach ($srcFiles as $file) {
                    if (!file_exists($file)) {
                        continue;
                    }
                    $contents = file_get_contents($file) . "\n";

                    if ($beforeMergeCallback !== null) {
                        $contents = $beforeMergeCallback($file, $contents);
                    }

                    $data .= $contents;
                }
                if (!$data) {
                    throw new \RuntimeException(sprintf("No content found in files:\n%s", implode("\n", $srcFiles)));
                }

                if ($targetFile) {
                    file_put_contents($targetFile, $data, LOCK_EX);
                } else {
                    return $data;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }
}
