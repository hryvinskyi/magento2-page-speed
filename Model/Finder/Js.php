<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Finder;

use Hryvinskyi\PageSpeedApi\Api\Finder\HtmlCommentInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\JsInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterfaceFactory;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterfaceFactory;

class Js extends AbstractRegexp implements JsInterface
{
    public array $needles = [
        "<script((?! type=).)*?>.*?<\/script>",
        "<script*?>.*?<\/script>",
        "<script[^>\w]*?>.*?<\/script>",
        "<script[^>]*?src=[^>]+?>.*?<\/script>",
        "<script[^>]*?[\"']text\/javascript[\"'][^>]*?>.*?<\/script>",
        "<script[^>]*?[\"']application\/javascript[\"'][^>]*?>.*?<\/script>",
        "<script[^>]*?[\"']javascript[\"'][^>]*?>.*?<\/script>",
        "<script[^>]*?text\/javascript[^>]*?>.*?<\/script>",
        "<script[^>]*?application\/javascript[^>]*?>.*?<\/script>",
        "<script[^>]*?javascript[^>]*?>.*?<\/script>",
    ];
    private TagInterfaceFactory $tagFactory;
    private HtmlCommentInterface $htmlComment;

    /**
     * @param RawInterfaceFactory $rawFactory
     * @param TagInterfaceFactory $tagFactory
     * @param HtmlCommentInterface $htmlComment
     */
    public function __construct(
        RawInterfaceFactory $rawFactory,
        TagInterfaceFactory $tagFactory,
        HtmlCommentInterface $htmlComment
    ) {
        parent::__construct($rawFactory);
        $this->tagFactory = $tagFactory;
        $this->htmlComment = $htmlComment;
    }

    /**
     * @inheridoc
     *
     * @return TagInterface[]
     * @throws \Exception
     */
    public function findAll(string $haystack, int $start = null, int $end = null): array
    {
        $pattern = "/" . implode('|', $this->needles) . "/is";
        /** @var TagInterface[] $result */

        $result = $this->findByNeedle($pattern, $haystack, $start, $end);
        $result = $this->excludeTagsWhichWithinHtmlComment($result, $haystack);

        return array_values($result);
    }

    /**
     * @inheridoc
     * @throws \Exception
     */
    public function findInline(string $haystack, int $start = null, int $end = null): array
    {
        $result = $this->findAll($haystack, $start, $end);
        foreach ($result as $key => $tag) {
            /** @var TagInterface $tag */
            $attributes = $tag->getAttributes();
            if (array_key_exists('src', $attributes)) {
                unset($result[$key]);
            }
        }
        return array_values($result);
    }

    /**
     * @inheridoc
     */
    public function findExternal(string $haystack, int $start = null, int $end = null): array
    {
        $result = $this->findAll($haystack, $start, $end);
        foreach ($result as $key => $tag) {
            /** @var TagInterface $tag */
            $attributes = $tag->getAttributes();
            if (!array_key_exists('src', $attributes)) {
                unset($result[$key]);
            }
        }
        return array_values($result);
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
     * @param TagInterface[] $tagList
     * @param string $haystack
     *
     * @return TagInterface[]
     * @throws \Exception
     */
    private function excludeTagsWhichWithinHtmlComment(array $tagList, string $haystack): array
    {
        $htmlCommentList = $this->htmlComment->findAll($haystack);
        foreach ($tagList as $key => $tag) {
            $start = $tag->getStart();
            foreach ($htmlCommentList as $htmlComment) {
                /** @var RawInterface $htmlComment */
                if ($htmlComment->getStart() < $start && $htmlComment->getEnd() > $start) {
                    unset($tagList[$key]);
                    break;
                }
            }
        }

        return $tagList;
    }
}
