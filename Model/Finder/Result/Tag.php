<?php
/**
 * Copyright (c) 2022-2026. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

namespace Hryvinskyi\PageSpeed\Model\Finder\Result;

use Hryvinskyi\Base\Helper\Html;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;

class Tag extends Raw implements TagInterface
{
    private array $attributes = [];

    /**
     * @inheridoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheridoc
     */
    public function setContent(string $content): void
    {
        parent::setContent($content);

        $this->attributes = $this->parseAttributesFromOpeningTag($content);
    }

    /**
     * @inheridoc
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->getAttributes());
    }

    /**
     * Update attributes on the opening tag without parsing inner content.
     *
     * @param array<string, string|null> $attributes Attributes to set or remove (null value removes the attribute).
     *
     * @return string The full tag content with updated attributes.
     */
    public function getContentWithUpdatedAttribute(array $attributes): string
    {
        $content = $this->getContent();

        if (!preg_match('/^(<\w+)((?:\s+[^>]*)?)>/s', $content, $matches)) {
            return $content;
        }

        $openingTagStart = $matches[1];
        $existingAttrsString = $matches[2];
        $openingTagFull = $matches[0];
        $restOfContent = substr($content, strlen($openingTagFull));

        $currentAttributes = $this->parseAttributeString($existingAttrsString);

        foreach ($attributes as $key => $value) {
            if ($value === null) {
                unset($currentAttributes[$key]);
            } else {
                $currentAttributes[$key] = $value;
            }
        }

        return $openingTagStart . Html::renderTagAttributes($currentAttributes) . '>' . $restOfContent;
    }

    /**
     * Parse attributes from the opening tag of an HTML element string.
     *
     * @param string $content The full HTML element string.
     *
     * @return array<string, string> Parsed attribute key-value pairs.
     */
    private function parseAttributesFromOpeningTag(string $content): array
    {
        if (!preg_match('/^<\w+((?:\s+[^>]*)?)>/s', $content, $matches)) {
            return [];
        }

        return $this->parseAttributeString($matches[1]);
    }

    /**
     * Parse an attribute string into key-value pairs.
     *
     * @param string $attrString The attribute portion of an opening tag (e.g. ' type="text" defer').
     *
     * @return array<string, string> Parsed attribute key-value pairs.
     */
    private function parseAttributeString(string $attrString): array
    {
        $attributes = [];

        preg_match_all(
            '/(\w[\w\-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+)))?/',
            $attrString,
            $attrMatches,
            PREG_SET_ORDER
        );

        foreach ($attrMatches as $match) {
            $key = $match[1];
            if (isset($match[4]) && $match[4] !== '') {
                $attributes[$key] = $match[4];
            } elseif (isset($match[3]) && $match[3] !== '') {
                $attributes[$key] = $match[3];
            } elseif (isset($match[2])) {
                $attributes[$key] = $match[2];
            } else {
                $attributes[$key] = $key;
            }
        }

        return $attributes;
    }
}
