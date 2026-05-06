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
    private const SCRIPT_PATTERN = '/<script\b[^>]*>.*?<\/script>/is';

    private const JAVASCRIPT_TYPES = [
        '' => true,
        'text/javascript' => true,
        'application/javascript' => true,
        'application/ecmascript' => true,
        'text/ecmascript' => true,
        'javascript' => true,
    ];

    /**
     * Kept for backward-compat with consumers that read $needles. The single
     * unified pattern above is what findAll() actually uses.
     *
     * @var string[]
     */
    public array $needles = [
        '<script\b[^>]*>.*?<\/script>',
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
        $matchCount = @preg_match_all(
            self::SCRIPT_PATTERN,
            $haystack,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if ($matchCount === false || $matchCount === 0) {
            return [];
        }

        $tagList = [];
        foreach ($matches[0] as [$content, $pos]) {
            if ($start !== null && $start > $pos) {
                continue;
            }
            if ($end !== null && $end < $pos) {
                continue;
            }
            if ($this->isJavaScriptScript($content) === false) {
                continue;
            }

            $tagList[] = $this->processResult($content, $pos);
        }

        if ($tagList === []) {
            return [];
        }

        $tagList = $this->excludeTagsWhichWithinHtmlComment($tagList, $haystack);
        if ($tagList === []) {
            return [];
        }

        return array_values($this->excludeTagsWhichWithinAttributeValue($tagList, $haystack));
    }

    /**
     * @inheridoc
     * @throws \Exception
     */
    public function findInline(string $haystack, int $start = null, int $end = null): array
    {
        $result = $this->findAll($haystack, $start, $end);
        foreach ($result as $key => $tag) {
            if (array_key_exists('src', $tag->getAttributes())) {
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
            if (array_key_exists('src', $tag->getAttributes()) === false) {
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
     * Type check on the raw matched text — avoids triggering attribute
     * parsing for non-JS payloads such as <script type="text/x-magento-init">,
     * <script type="application/ld+json">, etc.
     *
     * @param string $content
     *
     * @return bool
     */
    private function isJavaScriptScript(string $content): bool
    {
        $openTagEnd = strpos($content, '>');
        if ($openTagEnd === false) {
            return false;
        }

        $openTag = substr($content, 0, $openTagEnd);

        if (preg_match(
            '/\btype\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            $openTag,
            $matches
        ) !== 1) {
            return true;
        }

        $type = strtolower(trim((string)($matches[1] !== '' ? $matches[1] : ($matches[2] !== '' ? $matches[2] : ($matches[3] ?? '')))));

        return isset(self::JAVASCRIPT_TYPES[$type]);
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
        if ($htmlCommentList === []) {
            return $tagList;
        }

        // Sort comments by start so we can break out of the inner loop early.
        usort(
            $htmlCommentList,
            static fn(RawInterface $a, RawInterface $b): int => $a->getStart() <=> $b->getStart()
        );

        foreach ($tagList as $key => $tag) {
            $start = $tag->getStart();
            foreach ($htmlCommentList as $htmlComment) {
                if ($htmlComment->getStart() > $start) {
                    break;
                }
                if ($htmlComment->getEnd() > $start) {
                    unset($tagList[$key]);
                    break;
                }
            }
        }

        return $tagList;
    }

    /**
     * Skip script tags that live inside an HTML attribute value — e.g. the
     * unlikely but possible <div data-x="<script>…</script>"> case. Walk back
     * from the tag start to the nearest unescaped angle bracket: a "<" before a
     * ">" means we're inside an unclosed opening tag, i.e. an attribute value.
     *
     * @param TagInterface[] $tagList
     * @param string $haystack
     *
     * @return TagInterface[]
     */
    private function excludeTagsWhichWithinAttributeValue(array $tagList, string $haystack): array
    {
        foreach ($tagList as $key => $tag) {
            for ($i = $tag->getStart() - 1; $i >= 0; $i--) {
                $char = $haystack[$i];
                if ($char === '>') {
                    break;
                }
                if ($char === '<') {
                    unset($tagList[$key]);
                    break;
                }
            }
        }

        return $tagList;
    }
}
