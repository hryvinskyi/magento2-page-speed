<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Html;

use Hryvinskyi\PageSpeedApi\Api\Html\ReplaceIntoHtmlInterface;

class ReplaceIntoHtml implements ReplaceIntoHtmlInterface
{
    /**
     * @ingeritdoc
     */
    public function execute(string $html, string $replacement, int $start, int $end): string
    {
        $length = $end - $start + 1;
        return substr_replace($html, $replacement, $start, $length);
    }
}
