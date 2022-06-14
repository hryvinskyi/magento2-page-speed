<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Finder;

use Hryvinskyi\PageSpeedApi\Api\Finder\HtmlCommentInterface;

class HtmlComment extends AbstractRegexp implements HtmlCommentInterface
{
    public array $needles = [
        "/<!--[^\[>].*?-->/is",
    ];
}
