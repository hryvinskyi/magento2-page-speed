<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Unit\Model\Finder\Result;

use Hryvinskyi\Base\Model\Html\AttributeRenderer;
use Hryvinskyi\Base\Model\Html\CssOptions;
use Hryvinskyi\Base\Model\Html\HtmlEncoder;
use Hryvinskyi\Base\Model\Html\TextConverter;
use Hryvinskyi\Base\Model\Json\JsonEncoder;
use Hryvinskyi\PageSpeed\Model\Finder\Result\Tag;
use Hryvinskyi\PageSpeed\Model\Html\OpeningTagParser;
use Magento\Framework\Escaper;
use Magento\Framework\ZendEscaper;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    /**
     * The preferred order of attributes, as the container gives it to the renderer.
     */
    private const ATTRIBUTE_ORDER = [
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'srcset',
        'form',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media',
    ];

    /**
     * Attributes whose array value is expanded, as the container gives them to the renderer.
     */
    private const DATA_ATTRIBUTES = ['aria', 'data', 'data-ng', 'ng'];

    /**
     * @var Tag
     */
    private Tag $tag;

    protected function setUp(): void
    {
        $textConverter = new TextConverter();
        $htmlEncoder = new HtmlEncoder(new ZendEscaper(), new Escaper());

        $this->tag = new Tag(
            new AttributeRenderer(
                $htmlEncoder,
                new JsonEncoder(),
                new CssOptions($textConverter, true),
                $textConverter,
                self::ATTRIBUTE_ORDER,
                self::DATA_ATTRIBUTES,
                true
            ),
            $htmlEncoder,
            new OpeningTagParser()
        );
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
                '<script data-execute-marker="__lazyLoadMarker_require_js" src="/static/requirejs/require.js">'
                . '</script>',
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
            'attribute names with a colon, an at sign, a dot and an underscore' => [
                '<div x-on:click.prevent="a" @click="b" :class="c" xlink:href="d" data_e="f"></div>',
                ['x-on:click.prevent' => 'a', '@click' => 'b', ':class' => 'c', 'xlink:href' => 'd', 'data_e' => 'f'],
            ],
            'closing bracket inside a quoted value' => [
                '<img alt="a>b" src="a.jpg">',
                ['alt' => 'a>b', 'src' => 'a.jpg'],
            ],
            'self-closing slash is not an attribute' => [
                '<link rel="preload" />',
                ['rel' => 'preload'],
            ],
            'self-closing tag without a space' => [
                '<br/>',
                [],
            ],
            'content that is not a tag has no attributes' => [
                'plain text',
                [],
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
            'boolean attributes stay as written' => [
                '<script defer src="app.js"></script>',
                ['type' => 'lazyload'],
                '<script defer src="app.js" type="lazyload"></script>',
            ],
            'boolean attribute is removed with null' => [
                '<script src="app.js" defer async></script>',
                ['defer' => null],
                '<script src="app.js" async></script>',
            ],
            'boolean attribute takes a value in place' => [
                '<script defer src="app.js"></script>',
                ['defer' => 'defer'],
                '<script defer="defer" src="app.js"></script>',
            ],
            'attribute order is preserved' => [
                '<img data-b="2" alt="text" data-a="1" src="a.jpg" class="c">',
                ['loading' => 'lazy'],
                '<img data-b="2" alt="text" data-a="1" src="a.jpg" class="c" loading="lazy">',
            ],
            'a replaced attribute stays where it stands' => [
                '<img data-b="2" src="a.jpg" alt="text">',
                ['src' => 'b.jpg'],
                '<img data-b="2" src="b.jpg" alt="text">',
            ],
            'a replaced attribute takes the double quotes of the renderer' => [
                "<img src='a.jpg' alt='text'>",
                ['src' => 'b.jpg'],
                "<img src=\"b.jpg\" alt='text'>",
            ],
            'a replaced value is encoded' => [
                '<a id="a" href="/">x</a>',
                ['href' => '/?a=1&b="2"'],
                '<a id="a" href="/?a=1&amp;b=&quot;2&quot;">x</a>',
            ],
            'a removed first attribute goes with its whitespace' => [
                '<img src="a.jpg" alt="x">',
                ['src' => null],
                '<img alt="x">',
            ],
            'a removed middle attribute goes with its whitespace' => [
                "<img alt=\"x\"\n     src=\"a.jpg\"\n     width=\"1\">",
                ['src' => null],
                "<img alt=\"x\"\n     width=\"1\">",
            ],
            'whitespace between untouched attributes is kept' => [
                "<img   alt='x'\n\tsrc=a.jpg   >",
                ['loading' => 'lazy'],
                "<img   alt='x'\n\tsrc=a.jpg loading=\"lazy\"   >",
            ],
            'text between attributes stays as written' => [
                '<img src="a.jpg" / alt="x">',
                ['alt' => 'y'],
                '<img src="a.jpg" / alt="y">',
            ],
            'a replaced attribute keeps the whitespace before it' => [
                "<img alt=\"x\"\n     src=\"a.jpg\">",
                ['src' => 'b.jpg'],
                "<img alt=\"x\"\n     src=\"b.jpg\">",
            ],
            'every occurrence of a repeated attribute is replaced' => [
                '<img src="a.jpg" alt="x" src="b.jpg">',
                ['src' => 'c.jpg'],
                '<img src="c.jpg" alt="x" src="c.jpg">',
            ],
            'every occurrence of a repeated attribute is removed' => [
                '<img src="a.jpg" alt="x" src="b.jpg">',
                ['src' => null],
                '<img alt="x">',
            ],
            'new attributes follow the renderer order among themselves' => [
                '<img src="a.jpg">',
                ['data-x' => '1', 'title' => 't'],
                '<img src="a.jpg" title="t" data-x="1">',
            ],
            'untouched ampersand entity is not encoded again' => [
                '<img src="a.jpg?w=1&amp;h=2" alt="x">',
                ['alt' => 'y'],
                '<img src="a.jpg?w=1&amp;h=2" alt="y">',
            ],
            'untouched quote entity is not encoded again' => [
                '<div data-config="{&quot;key&quot;:&quot;val&quot;}" id="a"></div>',
                ['id' => 'b'],
                '<div data-config="{&quot;key&quot;:&quot;val&quot;}" id="b"></div>',
            ],
            'untouched apostrophe and angle bracket entities are not encoded again' => [
                '<img alt="it&#039;s &lt;b&gt;" src="a.jpg">',
                ['loading' => 'lazy'],
                '<img alt="it&#039;s &lt;b&gt;" src="a.jpg" loading="lazy">',
            ],
            'an already double-encoded value is left alone' => [
                '<img alt="&amp;quot;" src="a.jpg">',
                ['src' => 'b.jpg'],
                '<img alt="&amp;quot;" src="b.jpg">',
            ],
            'untouched bare ampersand stays bare' => [
                '<a href="a?x=1&y=2" id="a"></a>',
                ['id' => 'b'],
                '<a href="a?x=1&y=2" id="b"></a>',
            ],
            'untouched named entities survive' => [
                '<img alt="caf&eacute;&nbsp;bar" src="a.jpg">',
                ['loading' => 'lazy'],
                '<img alt="caf&eacute;&nbsp;bar" src="a.jpg" loading="lazy">',
            ],
            'untouched numeric entity survives' => [
                '<img alt="it&#8217;s" src="a.jpg">',
                ['loading' => 'lazy'],
                '<img alt="it&#8217;s" src="a.jpg" loading="lazy">',
            ],
            'untouched apos entity survives' => [
                '<img alt="it&apos;s" src="a.jpg">',
                ['loading' => 'lazy'],
                '<img alt="it&apos;s" src="a.jpg" loading="lazy">',
            ],
            'untouched single-quoted value keeps its quotes' => [
                "<div data-mage-init='{\"a\":1}' id=\"a\"></div>",
                ['id' => 'b'],
                "<div data-mage-init='{\"a\":1}' id=\"b\"></div>",
            ],
            'untouched bare value keeps its spelling' => [
                '<img width=10 src="a.jpg">',
                ['src' => 'b.jpg'],
                '<img width=10 src="b.jpg">',
            ],
            'a namespaced attribute survives' => [
                '<use xlink:href="#i" id="a"></use>',
                ['id' => 'b'],
                '<use xlink:href="#i" id="b"></use>',
            ],
            'a namespaced attribute is addressed by its full name' => [
                '<use xlink:href="#i" href="#j"></use>',
                ['xlink:href' => null],
                '<use href="#j"></use>',
            ],
            'a colon in an attribute name survives' => [
                '<div x-on:click.prevent="go()" id="a"></div>',
                ['id' => 'b'],
                '<div x-on:click.prevent="go()" id="b"></div>',
            ],
            'an at sign in an attribute name survives' => [
                '<div @click="go()" :class="c" id="a"></div>',
                ['id' => 'b'],
                '<div @click="go()" :class="c" id="b"></div>',
            ],
            'a closing bracket inside a quoted value does not end the tag' => [
                '<img alt="a>b" src="a.jpg">',
                ['loading' => 'lazy'],
                '<img alt="a>b" src="a.jpg" loading="lazy">',
            ],
            'a closing bracket inside a single-quoted value does not end the tag' => [
                "<div data-x='a>b' id=\"a\">c</div>",
                ['id' => 'b'],
                "<div data-x='a>b' id=\"b\">c</div>",
            ],
            'a stray apostrophe in a bare value does not swallow the content' => [
                "<script data-x=it's>var a = 'b';</script>",
                ['type' => 'lazyload'],
                "<script data-x=it's type=\"lazyload\">var a = 'b';</script>",
            ],
            'a new attribute goes before the self-closing slash' => [
                '<link rel="preload" href="a.css" />',
                ['data-x' => '1'],
                '<link rel="preload" href="a.css" data-x="1" />',
            ],
            'the self-closing slash survives a replacement' => [
                '<link rel="preload" href="a.css" />',
                ['href' => 'b.css'],
                '<link rel="preload" href="b.css" />',
            ],
            'the self-closing slash survives removing the last attribute' => [
                '<link rel="preload" href="a.css" />',
                ['href' => null],
                '<link rel="preload" />',
            ],
            'a self-closing tag without a space' => [
                '<br/>',
                ['data-x' => '1'],
                '<br data-x="1"/>',
            ],
            'a self-closing tag without attributes' => [
                '<br />',
                ['data-x' => '1'],
                '<br data-x="1" />',
            ],
            'a slash that ends a bare value is part of the value' => [
                '<link href=a.css/>',
                ['data-x' => '1'],
                '<link href=a.css/ data-x="1">',
            ],
            'a moved value is given as plain text' => [
                '<img src="a.jpg?w=1&amp;h=2">',
                ['src' => null, 'data-src' => 'a.jpg?w=1&h=2'],
                '<img data-src="a.jpg?w=1&amp;h=2">',
            ],
            'a plain value is encoded' => [
                '<a id="a">x</a>',
                ['href' => 'a&b'],
                '<a id="a" href="a&amp;b">x</a>',
            ],
            'an entity in a value stays one entity' => [
                '<a id="a">x</a>',
                ['title' => '&quot;x&quot; &amp; y'],
                '<a id="a" title="&quot;x&quot; &amp; y">x</a>',
            ],
            'an entity in a replacing value stays one entity' => [
                '<a href="/">x</a>',
                ['href' => '/?a=1&amp;b=2'],
                '<a href="/?a=1&amp;b=2">x</a>',
            ],
            'a name in another case is replaced and takes the spelling of the update' => [
                '<script TYPE="text/javascript">var x = 1;</script>',
                ['type' => 'lazyload'],
                '<script type="lazyload">var x = 1;</script>',
            ],
            'a name in another case is removed' => [
                '<script SRC="a.js" defer></script>',
                ['src' => null],
                '<script defer></script>',
            ],
            'an update in another case replaces a lower-case name' => [
                '<script src="a.js"></script>',
                ['SRC' => 'b.js'],
                '<script SRC="b.js"></script>',
            ],
            'every occurrence of a name is replaced whatever its case' => [
                '<img SRC="a.jpg" alt="x" Src="b.jpg">',
                ['src' => 'c.jpg'],
                '<img src="c.jpg" alt="x" src="c.jpg">',
            ],
            'a name in another case is not added a second time' => [
                '<script TYPE="text/javascript" SRC="a.js"></script>',
                ['type' => 'lazyload', 'src' => null, 'data-lazy-source' => 'a.js'],
                '<script type="lazyload" data-lazy-source="a.js"></script>',
            ],
            'content that is not a tag is returned as it is' => [
                'plain text',
                ['id' => 'a'],
                'plain text',
            ],
            'an element with a hyphen in its name is returned as it is' => [
                '<my-element id="a"></my-element>',
                ['id' => 'b'],
                '<my-element id="a"></my-element>',
            ],
            'an opening tag that never ends is returned as it is' => [
                '<img alt="a>b"',
                ['id' => 'b'],
                '<img alt="a>b"',
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

    public function testAttributeNamesAreReadWhole(): void
    {
        $this->tag->setContent('<use xlink:href="#i" href="#j"></use>');

        $this->assertTrue($this->tag->hasAttribute('xlink:href'));
        $this->assertSame('#i', $this->tag->getAttributes()['xlink:href']);
        $this->assertSame('#j', $this->tag->getAttributes()['href']);
        $this->assertFalse($this->tag->hasAttribute('xlink'));
    }

    /**
     * @dataProvider updateThatChangesNothingDataProvider
     *
     * @param string $content
     * @param array<string, string|null> $attributes
     */
    public function testAnUpdateThatChangesNothingReturnsTheSameBytes(string $content, array $attributes): void
    {
        $this->tag->setContent($content);

        $this->assertSame($content, $this->tag->getContentWithUpdatedAttribute($attributes));
    }

    /**
     * @return array<string, array{string, array<string, string|null>}>
     */
    public static function updateThatChangesNothingDataProvider(): array
    {
        $tag = "<img   src='a.jpg?w=1&amp;h=2'\n\talt=\"caf&eacute; a>b\" x-on:click=\"go()\" width=10 hidden />";

        return [
            'no changes' => [$tag, []],
            'removing an attribute the tag does not have' => [$tag, ['data-x' => null]],
            'replacing a double-quoted value with the same value' => ['<img id="a" src="a.jpg" >', ['id' => 'a']],
        ];
    }

    public function testGetAttributesKeepsValuesAsWrittenAfterAnUpdate(): void
    {
        $this->tag->setContent('<img src="a.jpg?w=1&amp;h=2" alt="&quot;x&quot;">');

        $this->tag->getContentWithUpdatedAttribute(['alt' => 'y']);

        $this->assertSame(
            ['src' => 'a.jpg?w=1&amp;h=2', 'alt' => '&quot;x&quot;'],
            $this->tag->getAttributes()
        );
    }

    /**
     * A caller moves a value from one attribute to another by reading it from getAttributes(), where it is spelled as
     * in the HTML; the loader then reads the new attribute with getAttribute() and must get the original URL.
     *
     * @dataProvider movedAttributeDataProvider
     *
     * @param string $content
     * @param string $name
     * @param string $expected
     */
    public function testAValueReadFromTheTagAndWrittenBackIsEncodedOnce(
        string $content,
        string $name,
        string $expected
    ): void {
        $this->tag->setContent($content);

        $this->assertSame(
            $expected,
            $this->tag->getContentWithUpdatedAttribute([
                $name => null,
                'data-lazy-source' => $this->tag->getAttributes()[$name],
            ])
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function movedAttributeDataProvider(): array
    {
        return [
            'a stylesheet link' => [
                '<link rel="stylesheet" href="x.css?a=1&amp;b=2">',
                'href',
                '<link rel="stylesheet" data-lazy-source="x.css?a=1&amp;b=2">',
            ],
            'a script' => [
                '<script src="a.js?a=1&amp;b=2"></script>',
                'src',
                '<script data-lazy-source="a.js?a=1&amp;b=2"></script>',
            ],
            'a value with a bare ampersand comes out valid' => [
                '<script src="a.js?a=1&b=2"></script>',
                'src',
                '<script data-lazy-source="a.js?a=1&amp;b=2"></script>',
            ],
        ];
    }

    public function testGetAttributesGivesNamesInLowerCaseAsHtmlReadsThem(): void
    {
        $this->tag->setContent('<script TYPE="Text/JavaScript" SRC="a.js"></script>');

        $this->assertSame(['type' => 'Text/JavaScript', 'src' => 'a.js'], $this->tag->getAttributes());
        $this->assertTrue($this->tag->hasAttribute('src'));
    }

    public function testAnUpperCaseSourceMovedToAnotherAttributeKeepsItsUrl(): void
    {
        $this->tag->setContent('<script TYPE="text/javascript" SRC="a.js"></script>');

        $attributes = $this->tag->getAttributes();

        $this->assertSame(
            '<script type="lazyload" data-lazy-source="a.js"></script>',
            $this->tag->getContentWithUpdatedAttribute([
                'type' => 'lazyload',
                'src' => null,
                'data-lazy-source' => $attributes['src'],
            ])
        );
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
