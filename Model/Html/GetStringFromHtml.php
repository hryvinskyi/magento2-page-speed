<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Html;

use Hryvinskyi\PageSpeedApi\Api\Html\GetStringFromHtmlInterface;

class GetStringFromHtml implements GetStringFromHtmlInterface
{
    /**
     * @inheridoc
     */
    public function execute(string $html, int $start, int $end): string
    {
        $length = $end - $start;

        return substr($html, $start, $length);
    }
}
