<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Test\Integration\Model;

use Hryvinskyi\PageSpeedApi\Api\GetContentFromTagInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The reader takes its parser from a preference and its document from a generated global-namespace factory, which a
 * unit test building it by hand does not exercise.
 */
class GetContentFromTagWiringTest extends TestCase
{
    /**
     * Resolves the reader through the real container.
     *
     * @return GetContentFromTagInterface
     */
    private function getContentFromTag(): GetContentFromTagInterface
    {
        $service = Bootstrap::getObjectManager()->get(GetContentFromTagInterface::class);
        self::assertInstanceOf(GetContentFromTagInterface::class, $service);

        return $service;
    }

    /**
     * The opening tag ends where the parser says, not at a `>` inside a quoted value.
     *
     * @return void
     */
    public function testElementIsReadPastAClosingBracketInAValue(): void
    {
        self::assertSame('var a=1;', $this->getContentFromTag()->execute('<script data-x="a>b">var a=1;</script>'));
    }

    /**
     * An element without a closing tag goes through the document the generated factory creates.
     *
     * @return void
     */
    public function testElementWithoutAClosingTagIsReadThroughADocument(): void
    {
        self::assertSame('var a=1;', $this->getContentFromTag()->execute('<script>var a=1;'));
    }
}
