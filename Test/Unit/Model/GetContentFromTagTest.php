<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Unit\Model;

use Hryvinskyi\PageSpeed\Model\GetContentFromTag;
use Hryvinskyi\PageSpeed\Model\Html\OpeningTagParser;
use PHPUnit\Framework\TestCase;

class GetContentFromTagTest extends TestCase
{
    /**
     * @var GetContentFromTag
     */
    private GetContentFromTag $getContentFromTag;

    protected function setUp(): void
    {
        $documentFactory = $this->createStub(\DOMDocumentFactory::class);
        $documentFactory->method('create')->willReturnCallback(static fn (): \DOMDocument => new \DOMDocument());

        $this->getContentFromTag = new GetContentFromTag(new OpeningTagParser(), $documentFactory);
    }

    /**
     * @dataProvider elementDataProvider
     *
     * @param string $element
     * @param string $expected
     */
    public function testExecuteReturnsWhatStandsBetweenTheTags(string $element, string $expected): void
    {
        $this->assertSame($expected, $this->getContentFromTag->execute($element));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function elementDataProvider(): array
    {
        return [
            'a script' => ['<script>var a=1;</script>', 'var a=1;'],
            'a style' => ['<style media="all">a{}</style>', 'a{}'],
            'an empty element' => ['<script src="a.js"></script>', ''],
            'several lines' => ["<script>\nvar a=1;\nvar b=2;\n</script>", "\nvar a=1;\nvar b=2;\n"],
            'a closing bracket in the content' => ['<script>if (a>b) {}</script>', 'if (a>b) {}'],
            'markup in the content' => ['<script>var a="<b>c</b>";</script>', 'var a="<b>c</b>";'],
            'a closing bracket in a double-quoted value' => ['<script data-x="a>b">var a=1;</script>', 'var a=1;'],
            'a closing bracket in a single-quoted value' => ["<script data-x='a>b'>var a=1;</script>", 'var a=1;'],
            'an element without a closing tag' => ['<script>var a=1;', 'var a=1;'],
            'an element without content' => ['<link rel="stylesheet" href="a.css">', ''],
            'text that is not an element' => ['plain text', ''],
            'an empty string' => ['', ''],
        ];
    }

    public function testMalformedMarkupLeavesNoParserErrorsBehind(): void
    {
        libxml_use_internal_errors(false);

        $this->getContentFromTag->execute('<script>var a=1;&bogus');

        $this->assertFalse(libxml_use_internal_errors());
    }
}
