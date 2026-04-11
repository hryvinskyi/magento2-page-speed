<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Unit\Model\Finder\Result;

use Hryvinskyi\PageSpeed\Model\Finder\Result\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    private Tag $tag;

    protected function setUp(): void
    {
        $this->tag = new Tag();
    }

    /**
     * @dataProvider setContentParsesAttributesDataProvider
     *
     * @param string $content
     * @param array<string, string> $expectedAttributes
     */
    public function testSetContentParsesAttributes(string $content, array $expectedAttributes): void
    {
        $this->tag->setContent($content);

        $this->assertSame($expectedAttributes, $this->tag->getAttributes());
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function setContentParsesAttributesDataProvider(): array
    {
        return [
            'single attribute with double quotes' => [
                '<script type="text/javascript">var x = 1;</script>',
                ['type' => 'text/javascript'],
            ],
            'multiple attributes' => [
                '<script type="text/javascript" src="https://example.com/script.js">content</script>',
                ['type' => 'text/javascript', 'src' => 'https://example.com/script.js'],
            ],
            'boolean attributes' => [
                '<script type="text/javascript" defer async>content</script>',
                ['type' => 'text/javascript', 'defer' => 'defer', 'async' => 'async'],
            ],
            'no attributes' => [
                '<script>var x = 1;</script>',
                [],
            ],
            'data attributes with hyphens' => [
                '<script data-execute-marker="__lazyLoadMarker_require_js" src="/static/requirejs/require.js"></script>',
                ['data-execute-marker' => '__lazyLoadMarker_require_js', 'src' => '/static/requirejs/require.js'],
            ],
            'single-quoted attribute values' => [
                "<script type='text/javascript'>content</script>",
                ['type' => 'text/javascript'],
            ],
            'without-quote attribute values' => [
                "<script type=text/javascript some-1=1 some2=2>content</script>",
                ['type' => 'text/javascript', 'some-1' => '1', 'some2' => '2'],
            ],
            'without-quote with-quote attribute values' => [
                "<script type=text/javascript src='https://www.example.com/s.js' some2=2>content</script>",
                ['type' => 'text/javascript', 'src' => 'https://www.example.com/s.js', 'some2' => '2'],
            ],
            'empty attribute value' => [
                '<script type="" src="https://example.com/script.js">content</script>',
                ['type' => '', 'src' => 'https://example.com/script.js'],
            ],
            'mixed boolean and valued attributes' => [
                '<script defer type="module" async nomodule>content</script>',
                ['defer' => 'defer', 'type' => 'module', 'async' => 'async', 'nomodule' => 'nomodule'],
            ],
            'attribute with special characters in value' => [
                '<script data-config="{&quot;key&quot;:&quot;val&quot;}">content</script>',
                ['data-config' => '{&quot;key&quot;:&quot;val&quot;}'],
            ],
            'extra whitespace between attributes' => [
                "<script   type=\"text/javascript\"   defer   src=\"test.js\"  >content</script>",
                ['type' => 'text/javascript', 'defer' => 'defer', 'src' => 'test.js'],
            ],
            'newlines in opening tag' => [
                "<script\n  type=\"text/javascript\"\n  src=\"test.js\"\n>content</script>",
                ['type' => 'text/javascript', 'src' => 'test.js'],
            ],
            'self-closing empty script' => [
                '<script type="text/javascript" src="app.js"></script>',
                ['type' => 'text/javascript', 'src' => 'app.js'],
            ],
            'style tag attributes' => [
                '<style type="text/css" media="all">body{}</style>',
                ['type' => 'text/css', 'media' => 'all'],
            ],
            'link tag attributes' => [
                '<link rel="stylesheet" href="/css/style.css">',
                ['rel' => 'stylesheet', 'href' => '/css/style.css'],
            ],
        ];
    }

    public function testHasAttributeReturnsTrueForExistingAttribute(): void
    {
        $this->tag->setContent('<script type="text/javascript">content</script>');

        $this->assertTrue($this->tag->hasAttribute('type'));
    }

    public function testHasAttributeReturnsFalseForMissingAttribute(): void
    {
        $this->tag->setContent('<script type="text/javascript">content</script>');

        $this->assertFalse($this->tag->hasAttribute('src'));
    }

    /**
     * @dataProvider getContentWithUpdatedAttributeDataProvider
     *
     * @param string $content
     * @param array<string, string|null> $attributes
     * @param string $expected
     */
    public function testGetContentWithUpdatedAttribute(string $content, array $attributes, string $expected): void
    {
        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute($attributes);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, array<string, string|null>, string}>
     */
    public static function getContentWithUpdatedAttributeDataProvider(): array
    {
        return [
            'update existing attribute' => [
                '<script type="text/javascript">var x = 1;</script>',
                ['type' => 'lazyload'],
                '<script type="lazyload">var x = 1;</script>',
            ],
            'add new attribute' => [
                '<script type="text/javascript">var x = 1;</script>',
                ['data-lazy-source' => 'https://example.com/script.js'],
                '<script type="text/javascript" data-lazy-source="https://example.com/script.js">var x = 1;</script>',
            ],
            'remove attribute with null' => [
                '<script type="text/javascript" src="https://example.com/script.js"></script>',
                ['src' => null],
                '<script type="text/javascript"></script>',
            ],
            'add attribute to script with no attributes' => [
                '<script>var x = 1;</script>',
                ['type' => 'lazyload'],
                '<script type="lazyload">var x = 1;</script>',
            ],
            'combined: update, remove, and add' => [
                '<script type="text/javascript" src="https://example.com/script.js"></script>',
                ['type' => 'lazyload', 'src' => null, 'data-lazy-source' => 'https://example.com/script.js'],
                '<script type="lazyload" data-lazy-source="https://example.com/script.js"></script>',
            ],
            'remove nonexistent attribute with null' => [
                '<script type="text/javascript">content</script>',
                ['src' => null],
                '<script type="text/javascript">content</script>',
            ],
            'remove all attributes' => [
                '<script type="text/javascript" src="app.js"></script>',
                ['type' => null, 'src' => null],
                '<script></script>',
            ],
            'update with empty changes' => [
                '<script type="text/javascript">var x = 1;</script>',
                [],
                '<script type="text/javascript">var x = 1;</script>',
            ],
            'preserve multiline inner content' => [
                "<script type=\"text/javascript\">\nvar x = 1;\nvar y = 2;\n</script>",
                ['type' => 'lazyload'],
                "<script type=\"lazyload\">\nvar x = 1;\nvar y = 2;\n</script>",
            ],
            'preserve inner content with closing script-like patterns' => [
                '<script type="text/javascript">var s = "<scr" + "ipt>test</scr" + "ipt>";</script>',
                ['type' => 'lazyload'],
                '<script type="lazyload">var s = "<scr" + "ipt>test</scr" + "ipt>";</script>',
            ],
        ];
    }

    public function testInnerContentWithHtmlClosingTagsIsPreserved(): void
    {
        $content = '<script type="text/javascript">'
            . "(function(){function getBadgeHTML(){return "
            . "'<div class=\"newsletter-popup-badge\">"
            . '<div class="newsletter-popup-badge-inner">'
            . '<div class="spinner"></div> Want to save 10%?'
            . "</div>"
            . "</div>';}})();"
            . '</script>';

        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute(['type' => 'lazyload']);

        $this->assertSame(3, substr_count($result, '</div>'), 'All three </div> closing tags must be preserved');
        $this->assertStringContainsString('type="lazyload"', $result);
        $this->assertStringContainsString('Want to save 10%?', $result);
        $this->assertStringContainsString('</script>', $result);
    }

    public function testInnerContentWithMultipleHtmlTagsIsPreserved(): void
    {
        $jsContent = "var html = '<table><tr><td>cell</td></tr></table>';";
        $content = '<script type="text/javascript">' . $jsContent . '</script>';

        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute(['type' => 'lazyload']);

        $this->assertStringContainsString('</td>', $result);
        $this->assertStringContainsString('</tr>', $result);
        $this->assertStringContainsString('</table>', $result);
    }

    public function testSpecialCharactersInAttributeValuesAreEscaped(): void
    {
        $this->tag->setContent('<script type="text/javascript">content</script>');

        $result = $this->tag->getContentWithUpdatedAttribute(['data-config' => 'key="value"&foo']);

        $this->assertStringContainsString('data-config="key=&quot;value&quot;&amp;foo"', $result);
    }

    public function testGetContentReturnsOriginalContent(): void
    {
        $content = '<script type="text/javascript">var x = 1;</script>';
        $this->tag->setContent($content);

        $this->assertSame($content, $this->tag->getContent());
    }

    public function testStartAndEndPositions(): void
    {
        $this->tag->setStart(100);
        $this->tag->setEnd(200);

        $this->assertSame(100, $this->tag->getStart());
        $this->assertSame(200, $this->tag->getEnd());
    }

    public function testInnerContentWithNestedScriptReferencesIsPreserved(): void
    {
        $jsContent = 'document.write(\'<script src="other.js"><\/script>\');';
        $content = '<script type="text/javascript">' . $jsContent . '</script>';

        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute(['type' => 'lazyload']);

        $this->assertStringContainsString($jsContent, $result);
    }

    public function testInnerContentWithJsonHtmlIsPreserved(): void
    {
        $jsContent = 'var config = {"template":"<div class=\"wrap\"><span></span></div>"};';
        $content = '<script type="text/x-magento-init">' . $jsContent . '</script>';

        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute(['type' => 'lazyload']);

        $this->assertStringContainsString('</span>', $result);
        $this->assertStringContainsString('</div>', $result);
        $this->assertStringContainsString($jsContent, $result);
    }

    public function testInnerContentWithRequireJsIsPreserved(): void
    {
        $jsContent = "require(['jquery'], function($) { $('<div></div>').appendTo('body'); });";
        $content = '<script type="text/javascript">' . $jsContent . '</script>';

        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute(['type' => 'lazyload']);

        $this->assertStringContainsString($jsContent, $result);
    }

    public function testSetContentOverwritesPreviousAttributes(): void
    {
        $this->tag->setContent('<script type="text/javascript" defer>content</script>');
        $this->assertSame(['type' => 'text/javascript', 'defer' => 'defer'], $this->tag->getAttributes());

        $this->tag->setContent('<script src="app.js" async>content</script>');
        $this->assertSame(['src' => 'app.js', 'async' => 'async'], $this->tag->getAttributes());

        $this->assertFalse($this->tag->hasAttribute('type'));
        $this->assertFalse($this->tag->hasAttribute('defer'));
    }

    public function testLazyLoadFullScenario(): void
    {
        $jsContent = "(function(){var el=document.createElement('div');"
            . "el.innerHTML='<div class=\"popup\"><form></form></div>';"
            . "document.body.appendChild(el);})();";
        $content = '<script type="text/javascript" src="https://cdn.example.com/popup.js">' . $jsContent . '</script>';

        $this->tag->setContent($content);

        $result = $this->tag->getContentWithUpdatedAttribute([
            'type' => 'lazyload',
            'src' => null,
            'data-lazy-source' => 'https://cdn.example.com/popup.js',
        ]);

        $this->assertStringContainsString('type="lazyload"', $result);
        $this->assertStringNotContainsString(' src=', $result);
        $this->assertStringContainsString('data-lazy-source="https://cdn.example.com/popup.js"', $result);
        $this->assertStringContainsString('</form>', $result);
        $this->assertStringContainsString('</div>', $result);
        $this->assertStringContainsString($jsContent, $result);
    }
}
