<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Integration\Model\Finder\Result;

use Hryvinskyi\PageSpeedApi\Api\Finder\CssInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\JsInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * A tag needs services, so it only works when the container builds it. These cases go through the generated factory
 * and the real finders, which a unit test constructing a tag by hand does not exercise.
 */
class TagWiringTest extends TestCase
{
    /**
     * Resolves a service through the real container.
     *
     * @template T of object
     * @param class-string<T> $type the interface or class to resolve
     * @return T
     */
    private function service(string $type): object
    {
        $service = Bootstrap::getObjectManager()->get($type);
        self::assertInstanceOf($type, $service);

        return $service;
    }

    /**
     * The generated factory hands the tag its renderer: new attributes take the configured order among themselves,
     * and the attributes the update does not name stay as they were written.
     *
     * @return void
     */
    public function testTagFromTheGeneratedFactoryUpdatesItsAttributes(): void
    {
        $tag = $this->service(TagInterfaceFactory::class)->create();
        $tag->setContent('<img alt="&quot;x&quot;" src="a.jpg?w=1&amp;h=2" width="10">');

        self::assertSame(
            '<img alt="&quot;x&quot;" src="b.jpg?w=1&amp;h=2" width="10" title="t" data-a="1">',
            $tag->getContentWithUpdatedAttribute(['src' => 'b.jpg?w=1&h=2', 'data-a' => '1', 'title' => 't'])
        );
    }

    /**
     * HTML reads attribute names whatever their case, so a script written in upper case counts as external.
     *
     * @return void
     */
    public function testTheFinderReadsAnUpperCaseSource(): void
    {
        $finder = $this->service(JsInterface::class);
        $script = '<SCRIPT SRC="a.js"></SCRIPT>';

        self::assertCount(1, $finder->findExternal($script));
        self::assertSame([], $finder->findInline($script));
    }

    /**
     * The tags the stylesheet finder returns come from the container too.
     *
     * @return void
     */
    public function testStylesheetFinderReturnsWorkingTags(): void
    {
        $results = $this->service(CssInterface::class)->findAll(
            '<link rel="stylesheet" href="a.css?v=1&amp;x=2" media="all">'
        );

        self::assertCount(1, $results);
        $tag = array_values($results)[0];
        self::assertInstanceOf(TagInterface::class, $tag);
        self::assertSame(
            '<link rel="stylesheet" href="a.css?v=1&amp;x=2" media="print">',
            $tag->getContentWithUpdatedAttribute(['media' => 'print'])
        );
    }

    /**
     * The generated factory hands the tag its encoder and its parser: a value read from the tag and written to another
     * attribute keeps its one encoding, and a `>` inside a quoted value does not end the tag.
     *
     * @return void
     */
    public function testValueMovedBetweenAttributesIsEncodedOnce(): void
    {
        $results = $this->service(CssInterface::class)->findExternal(
            '<link rel="stylesheet" href="a.css?v=1&amp;x=2" media="(width>=768px)">'
        );

        self::assertCount(1, $results);
        $tag = array_values($results)[0];
        self::assertInstanceOf(TagInterface::class, $tag);
        $attributes = $tag->getAttributes();
        self::assertIsString($attributes['href']);
        self::assertSame(
            '<link rel="stylesheet" media="(width>=768px)" data-lazy-source="a.css?v=1&amp;x=2">',
            $tag->getContentWithUpdatedAttribute(['href' => null, 'data-lazy-source' => $attributes['href']])
        );
    }

    /**
     * The script finder gets its parser from the container: a `type` that follows a `>` inside a quoted value counts.
     *
     * @return void
     */
    public function testScriptFinderReadsTheTypeAfterAClosingBracketInAValue(): void
    {
        self::assertSame(
            [],
            $this->service(JsInterface::class)->findAll('<script data-x="a>b" type="application/json">{}</script>')
        );
    }

    /**
     * The tags the script finder returns come from the container too.
     *
     * @return void
     */
    public function testScriptFinderReturnsWorkingTags(): void
    {
        $results = $this->service(JsInterface::class)->findAll('<script src="a.js?v=1&amp;x=2"></script>');

        self::assertCount(1, $results);
        self::assertSame(
            '<script src="a.js?v=1&amp;x=2" type="lazyload"></script>',
            array_values($results)[0]->getContentWithUpdatedAttribute(['type' => 'lazyload'])
        );
    }
}
