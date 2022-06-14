<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Html;

use Hryvinskyi\PageSpeedApi\Api\Html\InsertStringBeforeHeadEndInterface;

class InsertStringBeforeHeadEnd implements InsertStringBeforeHeadEndInterface
{
    /**
     * @inheritdoc
     */
    public function execute(string $insertString, string $html): string
    {
        return str_replace('</head>', $insertString . "\n</head>", $html);
    }
}
