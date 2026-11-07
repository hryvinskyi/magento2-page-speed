<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Unit\Model\Html;

use Hryvinskyi\PageSpeed\Model\Html\OpeningTagParser;
use PHPUnit\Framework\TestCase;

class OpeningTagParserTest extends TestCase
{
    /**
     * @dataProvider elementDataProvider
     *
     * @param string $element
     * @param array{name: string, attributes: string, rest: string} $expected
     */
    public function testParseSplitsAnElementAtTheEndOfItsOpeningTag(string $element, array $expected): void
    {
        $this->assertSame($expected, (new OpeningTagParser())->parse($element));
    }

    /**
     * @return array<string, array{string, array{name: string, attributes: string, rest: string}}>
     */
    public static function elementDataProvider(): array
    {
        return [
            'no attributes' => [
                '<script>var a;</script>',
                ['name' => 'script', 'attributes' => '', 'rest' => 'var a;</script>'],
            ],
            'attributes are kept as written' => [
                "<script  type='a'\n  defer >b</script>",
                ['name' => 'script', 'attributes' => "  type='a'\n  defer ", 'rest' => 'b</script>'],
            ],
            'the name keeps its case' => [
                '<SCRIPT SRC="a.js"></SCRIPT>',
                ['name' => 'SCRIPT', 'attributes' => ' SRC="a.js"', 'rest' => '</SCRIPT>'],
            ],
            'a closing bracket in a double-quoted value' => [
                '<a data-x="b>c">d</a>',
                ['name' => 'a', 'attributes' => ' data-x="b>c"', 'rest' => 'd</a>'],
            ],
            'a closing bracket in a single-quoted value' => [
                "<a data-x='b>c'>d</a>",
                ['name' => 'a', 'attributes' => " data-x='b>c'", 'rest' => 'd</a>'],
            ],
            'spaces around the equals sign' => [
                '<a data-x = "b>c">d</a>',
                ['name' => 'a', 'attributes' => ' data-x = "b>c"', 'rest' => 'd</a>'],
            ],
            'a stray apostrophe in a bare value does not open a value' => [
                "<a data-x=it's>'d'</a>",
                ['name' => 'a', 'attributes' => " data-x=it's", 'rest' => "'d'</a>"],
            ],
            'a quote that does not follow an equals sign does not open a value' => [
                '<a b"c>d"</a>',
                ['name' => 'a', 'attributes' => ' b"c', 'rest' => 'd"</a>'],
            ],
            'a quoted value that never closes ends at the first bracket' => [
                '<a data-x="b>c</a>',
                ['name' => 'a', 'attributes' => ' data-x="b', 'rest' => 'c</a>'],
            ],
            'a self-closing tag' => [
                '<br/>',
                ['name' => 'br', 'attributes' => '/', 'rest' => ''],
            ],
            'an element with nothing after its opening tag' => [
                '<link href="a.css">',
                ['name' => 'link', 'attributes' => ' href="a.css"', 'rest' => ''],
            ],
        ];
    }

    /**
     * @dataProvider notAnOpeningTagDataProvider
     *
     * @param string $text
     */
    public function testParseReturnsNullWhenTheTextDoesNotStartWithAnOpeningTag(string $text): void
    {
        $this->assertNull((new OpeningTagParser())->parse($text));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAnOpeningTagDataProvider(): array
    {
        return [
            'an empty string' => [''],
            'plain text' => ['plain text'],
            'text before the tag' => [' <a>b</a>'],
            'a closing tag' => ['</a>'],
            'a comment' => ['<!-- a -->'],
            'a hyphen in the name' => ['<my-element id="a"></my-element>'],
            'a tag that never ends' => ['<img alt="a>b"'],
        ];
    }
}
