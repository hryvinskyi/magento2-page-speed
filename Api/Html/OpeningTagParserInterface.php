<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Api\Html;

/**
 * Reads the opening tag of an HTML element: where it ends, and the attributes it holds.
 *
 * @api
 */
interface OpeningTagParserInterface
{
    /**
     * One step through the text that follows a tag's name, as alternatives of a regular expression.
     *
     * A quote only opens a value right after `=`, so a `>` inside a quoted value does not end the tag, and a stray
     * apostrophe in an unquoted value does not swallow the element's content. Repeat it possessively (`*+`), which
     * never retries a quoted value as plain text; a pattern that has to read a tag as a whole is built on this text.
     */
    public const ATTRIBUTE_TEXT = '[^>=]|=\s*"[^"]*"|=\s*\'[^\']*\'|=';

    /**
     * Splits an element's text into its opening tag and what follows it.
     *
     * @param string $element the element's text, starting with its `<`
     * @return array{name: string, attributes: string, rest: string}|null the tag's name as written, the text between
     *     the name and the closing `>` exactly as written, and everything after that `>`; `null` when the text does
     *     not start with an opening tag
     */
    public function parse(string $element): ?array;

    /**
     * Finds the attributes in the text between a tag's name and its closing `>`, with where each one stands.
     *
     * @param string $attributes the attribute text, as `parse()` returns it (e.g. ' type="text" defer')
     * @return list<array{name: string, value: string, whitespace: string, source: string, start: int}>
     *     `name` as written; `value` as the HTML spells it, or the name of a boolean attribute; `source` is the
     *     attribute's exact text with its leading whitespace, and `start` is where that text begins
     */
    public function findAttributes(string $attributes): array;
}
