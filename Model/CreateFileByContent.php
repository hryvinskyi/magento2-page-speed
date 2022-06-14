<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\CreateFileByContentInterface;
use Hryvinskyi\PageSpeedApi\Api\PutContentInFileInterface;

class CreateFileByContent implements CreateFileByContentInterface
{
    private PutContentInFileInterface $putContentInFile;

    /**
     * @param PutContentInFileInterface $putContentInFile
     */
    public function __construct(PutContentInFileInterface $putContentInFile)
    {
        $this->putContentInFile = $putContentInFile;
    }

    /**
     * @inheridoc
     */
    public function execute(string $content, string $dir, string $extension): string
    {
        $filename = md5($content) . '-' . strlen($content) . '.' . $extension;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($path)) {
            return $path;
        }

        $this->putContentInFile->execute($content, $path);

        return $path;
    }
}
