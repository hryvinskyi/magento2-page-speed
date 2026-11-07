<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Html;

use Hryvinskyi\PageSpeed\Api\Html\OpeningTagParserInterface;

/**
 * Reads an opening tag with a regular expression: its name, then everything up to the `>` that ends it.
 */
class OpeningTagParser implements OpeningTagParserInterface
{
    private const OPENING_TAG = '/^<(\w+)(?=[\s\/>])((?:' . self::ATTRIBUTE_TEXT . ')*+)>/';

    /**
     * One attribute of an opening tag: its leading whitespace, its name, and its value when it has one.
     *
     * The branch reset gives the three spellings of a value one capture group.
     */
    private const ATTRIBUTE = '/(\s*)([\w:@.\-]+)(?:\s*=\s*(?|"([^"]*)"|\'([^\']*)\'|(\S+)))?/';

    /**
     * @inheritDoc
     */
    public function parse(string $element): ?array
    {
        if (preg_match(self::OPENING_TAG, $element, $matches) !== 1) {
            return null;
        }

        return [
            'name' => $matches[1],
            'attributes' => $matches[2],
            'rest' => substr($element, strlen($matches[0])),
        ];
    }

    /**
     * @inheritDoc
     */
    public function findAttributes(string $attributes): array
    {
        preg_match_all(self::ATTRIBUTE, $attributes, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $found = [];
        foreach ($matches as $match) {
            $found[] = [
                'name' => $match[2][0],
                'value' => $match[3][0] ?? $match[2][0],
                'whitespace' => $match[1][0],
                'source' => $match[0][0],
                'start' => $match[0][1],
            ];
        }

        return $found;
    }
}
