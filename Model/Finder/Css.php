<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Finder;

use Hryvinskyi\PageSpeed\Api\Html\OpeningTagParserInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\CssInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterfaceFactory;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterfaceFactory;

class Css extends AbstractRegexp implements CssInterface
{
    /**
     * The `rel` attribute of a stylesheet link. The lookbehind keeps it from matching the end of a longer name.
     */
    private const STYLESHEET_REL = '(?<![\w:@.\-=])rel\s*=\s*(?:"stylesheet"|\'stylesheet\')';

    /**
     * Patterns of an external stylesheet link, without delimiters.
     *
     * The tag is read as the opening tag parser reads it, so `rel` is only looked for outside quoted values and a `>`
     * inside one does not end the link.
     *
     * @var string[]
     */
    public array $needles = [
        '<link(?=[\s\/>])'
        . '(?:(?!' . self::STYLESHEET_REL . ')(?:' . OpeningTagParserInterface::ATTRIBUTE_TEXT . '))*+'
        . self::STYLESHEET_REL
        . '(?:' . OpeningTagParserInterface::ATTRIBUTE_TEXT . ')*+>'
    ];

    /**
     * Patterns of an inline style element, without delimiters.
     *
     * @var string[]
     */
    public array $needlesStyles = [
        '<style.*?>.*?<\/style>'
    ];

    /**
     * @param RawInterfaceFactory $rawFactory creates the plain match each tag is built from
     * @param TagInterfaceFactory $tagFactory creates the tags the finder returns
     */
    public function __construct(
        RawInterfaceFactory $rawFactory,
        private readonly TagInterfaceFactory $tagFactory
    ) {
        parent::__construct($rawFactory);
    }

    /**
     * Finds the stylesheet links and the style elements of the haystack, in source order.
     *
     * @param string $haystack The HTML to search.
     * @param int|null $start Position before which a match is skipped.
     * @param int|null $end Position after which a match is skipped.
     *
     * @return RawInterface[] Every element is a tag, created by processResult().
     * @throws \Exception When a pattern fails for a reason other than the PCRE limits.
     */
    public function findAll(string $haystack, ?int $start = null, ?int $end = null): array
    {
        $result = array_merge(
            $this->findExternal($haystack, $start, $end),
            $this->findInline($haystack, $start, $end)
        );
        uasort($result, [$this, 'sortByStartPos']);

        return $result;
    }

    /**
     * Creates the tag of one match.
     *
     * @param string $source The matched HTML.
     * @param int $pos The position of the match in the haystack.
     *
     * @return TagInterface The tag, with its content and position.
     * @throws \Exception When the plain match cannot be created.
     */
    public function processResult(string $source, int $pos): TagInterface
    {
        $raw = parent::processResult($source, $pos);

        $result = $this->tagFactory->create();
        $result->setContent($raw->getContent());
        $result->setStart($raw->getStart());
        $result->setEnd($raw->getEnd());

        return $result;
    }

    /**
     * Finds the style elements of the haystack.
     *
     * @param string $haystack The HTML to search.
     * @param int|null $start Position before which a match is skipped.
     * @param int|null $end Position after which a match is skipped.
     *
     * @return RawInterface[] Every element is a tag, created by processResult().
     * @throws \Exception When the pattern fails for a reason other than the PCRE limits.
     */
    public function findInline($haystack, $start = null, $end = null)
    {
        $pattern = "/" . implode('|', $this->needlesStyles) . "/is";

        return array_values($this->findByNeedle($pattern, $haystack, $start, $end));
    }

    /**
     * Finds the stylesheet links of the haystack.
     *
     * @param string $haystack The HTML to search.
     * @param int|null $start Position before which a match is skipped.
     * @param int|null $end Position after which a match is skipped.
     *
     * @return RawInterface[] Every element is a tag, created by processResult().
     * @throws \Exception When the pattern fails for a reason other than the PCRE limits.
     */
    public function findExternal($haystack, $start = null, $end = null)
    {
        $pattern = "/" . implode('|', $this->needles) . "/is";

        return array_values($this->findByNeedle($pattern, $haystack, $start, $end));
    }

    /**
     * Orders two results by their position in the haystack.
     *
     * @param RawInterface $a The first result.
     * @param RawInterface $b The second result.
     *
     * @return int Negative, zero or positive, as sorting functions expect.
     */
    private function sortByStartPos(RawInterface $a, RawInterface $b): int
    {
        return $a->getStart() - $b->getStart();
    }
}
