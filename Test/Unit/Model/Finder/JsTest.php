<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Unit\Model\Finder;

use Hryvinskyi\PageSpeed\Model\Finder\Js;
use Hryvinskyi\PageSpeed\Model\Finder\Result\Raw;
use Hryvinskyi\PageSpeed\Model\Html\OpeningTagParser;
use Hryvinskyi\PageSpeedApi\Api\Finder\HtmlCommentInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterfaceFactory;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterfaceFactory;
use PHPUnit\Framework\TestCase;

class JsTest extends TestCase
{
    /**
     * @var Js
     */
    private Js $js;

    protected function setUp(): void
    {
        $rawFactory = $this->createStub(RawInterfaceFactory::class);
        $rawFactory->method('create')->willReturnCallback(static fn (): RawInterface => new Raw());

        $tagFactory = $this->createStub(TagInterfaceFactory::class);
        $tagFactory->method('create')->willReturnCallback(fn (): TagInterface => $this->createTagDouble());

        $htmlComment = $this->createStub(HtmlCommentInterface::class);
        $htmlComment->method('findAll')->willReturn([]);

        $this->js = new Js($rawFactory, $tagFactory, $htmlComment, new OpeningTagParser());
    }

    /**
     * @dataProvider javaScriptDataProvider
     *
     * @param string $script
     */
    public function testAJavaScriptElementIsFound(string $script): void
    {
        $this->assertSame([$script], $this->contentsOf($this->js->findAll($script)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function javaScriptDataProvider(): array
    {
        return [
            'no type' => ['<script>var a = 1;</script>'],
            'an empty type' => ['<script type="">var a = 1;</script>'],
            'a JavaScript type' => ['<script type="text/javascript">var a = 1;</script>'],
            'a type in another case' => ['<script TYPE="TEXT/JAVASCRIPT">var a = 1;</script>'],
            'a single-quoted type' => ["<script type='application/javascript'>var a = 1;</script>"],
            'a bare type' => ['<script type=text/javascript>var a = 1;</script>'],
            'a closing bracket in a value' => ['<script data-x="a>b" src="a.js"></script>'],
            'a JavaScript type after a closing bracket in a value' => [
                '<script data-x="a>b" type="text/javascript">var a = 1;</script>',
            ],
            'another attribute ending in type' => ['<script data-type="application/json">var a = 1;</script>'],
            'a URL holding the word type' => ['<script src="https://x.test/a.js?type=module"></script>'],
            'a type with no value' => ['<script type>var a = 1;</script>'],
            'a namespaced attribute ending in type' => ['<script x:type="application/json">var a = 1;</script>'],
        ];
    }

    /**
     * @dataProvider notJavaScriptDataProvider
     *
     * @param string $script
     */
    public function testAnElementThatIsNotJavaScriptIsNotFound(string $script): void
    {
        $this->assertSame([], $this->contentsOf($this->js->findAll($script)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notJavaScriptDataProvider(): array
    {
        return [
            'JSON' => ['<script type="application/json">{}</script>'],
            'a bare type' => ['<script type=application/ld+json>{}</script>'],
            'JSON after a closing bracket in a double-quoted value' => [
                '<script data-x="a>b" type="application/json">{}</script>',
            ],
            'JSON after a value holding the word type' => [
                '<script data-x="type=text/javascript" type="application/json">{}</script>',
            ],
            'JSON after a closing bracket in a single-quoted value' => [
                "<script data-x='a>b' type='application/json'>{}</script>",
            ],
        ];
    }

    public function testFindAllHonoursTheStartAndEndPositions(): void
    {
        $first = '<script>var a = 1;</script>';
        $second = '<script>var b = 2;</script>';

        $this->assertSame([$second], $this->contentsOf($this->js->findAll($first . $second, 1)));
        $this->assertSame([$first], $this->contentsOf($this->js->findAll($first . $second, null, 1)));
    }

    /**
     * Creates a result that only carries its content and position, which is all the finder reads here.
     *
     * @return TagInterface
     */
    private function createTagDouble(): TagInterface
    {
        return new class extends Raw implements TagInterface {
            /**
             * @return array<string, string>
             */
            public function getAttributes(): array
            {
                return [];
            }

            public function hasAttribute(string $key): bool
            {
                return false;
            }

            /**
             * @param array<string, string|null> $attributes
             */
            public function getContentWithUpdatedAttribute(array $attributes): string
            {
                return $this->getContent();
            }
        };
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
