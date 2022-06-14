<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Finder;

use Hryvinskyi\PageSpeed\Model\Finder\Result\Raw;
use Hryvinskyi\PageSpeedApi\Api\Finder\CssInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterfaceFactory;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterfaceFactory;
use phpDocumentor\Reflection\DocBlock\TagFactory;

class Css extends AbstractRegexp implements CssInterface
{
    public array $needles = [
        "<link[^>]+?rel\\s*=\\s*['\"]stylesheet['\"]+?[^>]*>"
    ];

    public array $needlesStyles = [
        '<style.*?>.*?<\/style>'
    ];

    private TagInterfaceFactory $tagFactory;

    /**
     * @param RawInterfaceFactory $rawFactory
     * @param TagInterfaceFactory $tagFactory
     */
    public function __construct(RawInterfaceFactory $rawFactory, TagInterfaceFactory $tagFactory)
    {
        parent::__construct($rawFactory);
        $this->tagFactory = $tagFactory;
    }

    /**
     * @inheridoc
     *
     * @return TagInterface[]
     * @throws \Exception
     */
    public function findAll(string $haystack, int $start = null, int $end = null): array
    {
        $result = array_merge(
            $this->findExternal($haystack, $start, $end),
            $this->findInline($haystack, $start, $end)
        );
        uasort($result, [$this, 'sortByStartPos']);

        return $result;
    }

    /**
     * @inheridoc
     * @throws \Exception
     */
    public function processResult(string $source, int $pos): TagInterface
    {
        $raw = parent::processResult($source, $pos);

        /** @var TagInterface $result */
        $result = $this->tagFactory->create();
        $result->setContent($raw->getContent());
        $result->setStart($raw->getStart());
        $result->setEnd($raw->getEnd());

        return $result;
    }

    /**
     * @param $haystack
     * @param $start
     * @param $end
     * @return array|\Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface[]
     * @throws \Exception
     */
    public function findInline($haystack, $start = null, $end = null)
    {
        $pattern = "/" . implode('|', $this->needlesStyles) . "/is";

        return array_values($this->findByNeedle($pattern, $haystack, $start, $end));
    }

    /**
     * @param $haystack
     * @param $start
     * @param $end
     * @return array|\Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface[]
     * @throws \Exception
     */
    public function findExternal($haystack, $start = null, $end = null)
    {
        $pattern = "/" . implode('|', $this->needles) . "/is";

        return array_values($this->findByNeedle($pattern, $haystack, $start, $end));
    }

    /**
     * @param Raw $a
     * @param Raw $b
     *
     * @return int
     */
    private function sortByStartPos(Raw $a, Raw $b): int
    {
        return $a->getStart() - $b->getStart();
    }
}
