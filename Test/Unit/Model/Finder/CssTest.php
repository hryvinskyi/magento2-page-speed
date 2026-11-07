<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Unit\Model\Finder;

use Hryvinskyi\Base\Api\Html\AttributeRendererInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\PageSpeed\Model\Finder\Css;
use Hryvinskyi\PageSpeed\Model\Finder\Result\Raw;
use Hryvinskyi\PageSpeed\Model\Finder\Result\Tag;
use Hryvinskyi\PageSpeed\Model\Html\OpeningTagParser;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterfaceFactory;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterfaceFactory;
use PHPUnit\Framework\TestCase;

class CssTest extends TestCase
{
    /**
     * @var Css
     */
    private Css $css;

    protected function setUp(): void
    {
        $rawFactory = $this->createStub(RawInterfaceFactory::class);
        $rawFactory->method('create')->willReturnCallback(static fn (): RawInterface => new Raw());

        $attributeRenderer = $this->createStub(AttributeRendererInterface::class);
        $htmlEncoder = $this->createStub(HtmlEncoderInterface::class);
        $tagFactory = $this->createStub(TagInterfaceFactory::class);
        $tagFactory->method('create')->willReturnCallback(
            static fn (): TagInterface => new Tag($attributeRenderer, $htmlEncoder, new OpeningTagParser())
        );

        $this->css = new Css($rawFactory, $tagFactory);
    }

    public function testFindAllReturnsStylesheetsAndStylesAsTagsInSourceOrder(): void
    {
        $style = '<style media="print">a{}</style>';
        $link = '<link rel="stylesheet" href="a.css">';
        $html = $style . '<p></p>' . $link . $style;

        $result = array_values($this->css->findAll($html));

        $this->assertSame(
            [
                [$style, 0, 31],
                [$link, 39, 74],
                [$style, 75, 106],
            ],
            array_map(
                static fn (RawInterface $tag): array => [$tag->getContent(), $tag->getStart(), $tag->getEnd()],
                $result
            )
        );
        $this->assertContainsOnlyInstancesOf(TagInterface::class, $result);
    }

    public function testFindExternalAndFindInlineSeparateTheTwoKinds(): void
    {
        $style = '<style>a{}</style>';
        $link = '<link rel="stylesheet" href="a.css">';
        $html = $link . $style;

        $this->assertSame([$link], $this->contentsOf($this->css->findExternal($html)));
        $this->assertSame([$style], $this->contentsOf($this->css->findInline($html)));
    }

    public function testFindAllHonoursTheStartAndEndPositions(): void
    {
        $first = '<style>a{}</style>';
        $second = '<style>b{}</style>';

        $this->assertSame([$second], $this->contentsOf($this->css->findAll($first . $second, 1)));
        $this->assertSame([$first], $this->contentsOf($this->css->findAll($first . $second, null, 1)));
    }

    /**
     * @dataProvider stylesheetLinkDataProvider
     *
     * @param string $link
     */
    public function testAStylesheetLinkIsFoundWhole(string $link): void
    {
        $html = '<p>a</p>' . $link . '<p>b</p>';

        $this->assertSame([$link], $this->contentsOf($this->css->findExternal($html)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function stylesheetLinkDataProvider(): array
    {
        return [
            'a closing bracket in a value after rel' => ['<link rel="stylesheet" href="a.css" media="(width>=768px)">'],
            'a closing bracket in a value before rel' => [
                '<link media="(width>=768px)" rel="stylesheet" href="a.css">',
            ],
            'a closing bracket in a single-quoted value' => ["<link rel='stylesheet' media='(width>=768px)'>"],
            'a self-closing link' => ['<link rel="stylesheet" href="a.css" />'],
            'no space before rel' => ['<link href="a.css"rel="stylesheet">'],
            'the case of the name and the value' => ['<LINK REL="STYLESHEET" HREF="a.css">'],
            'spaces around the equals sign' => ['<link rel = "stylesheet" href="a.css">'],
            'over several lines' => ["<link\n  rel=\"stylesheet\"\n  href=\"a.css\"\n>"],
            'an equals sign in a bare value' => ['<link href=a.css?v=1 rel="stylesheet">'],
        ];
    }

    /**
     * @dataProvider notAStylesheetLinkDataProvider
     *
     * @param string $link
     */
    public function testALinkThatIsNotAStylesheetIsNotFound(string $link): void
    {
        $this->assertSame([], $this->contentsOf($this->css->findExternal($link)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAStylesheetLinkDataProvider(): array
    {
        return [
            'another rel' => ['<link rel="preload" href="a.css">'],
            'rel inside a quoted value' => ['<link data-x=\'rel="stylesheet"\' rel="preload">'],
            'rel as the end of another name' => ['<link data-rel="stylesheet" rel="preload">'],
            'stylesheet after a closing bracket in a value' => ['<link rel="preload" title="a>rel=\'stylesheet\'">'],
            'another element' => ['<linkage rel="stylesheet">'],
        ];
    }

    public function testAFoundLinkReadsAClosingBracketInAValue(): void
    {
        $link = '<link rel="stylesheet" href="a.css" media="(width>=768px)">';

        $tag = array_values($this->css->findExternal($link))[0];

        $this->assertInstanceOf(TagInterface::class, $tag);
        $this->assertSame(
            ['rel' => 'stylesheet', 'href' => 'a.css', 'media' => '(width>=768px)'],
            $tag->getAttributes()
        );
    }

    /**
     * @param RawInterface[] $results
     * @return string[]
     */
    private function contentsOf(array $results): array
    {
        return array_values(array_map(static fn (RawInterface $result): string => $result->getContent(), $results));
    }
}
