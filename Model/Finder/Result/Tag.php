<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

namespace Hryvinskyi\PageSpeed\Model\Finder\Result;

use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use function _PHPStan_c862bb974\RingCentral\Psr7\str;

class Tag extends Raw implements TagInterface
{
    private array $attributes = [];
    private string $contentWithoutTag;

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

        $dom = new \DOMDocument();
        @$dom->loadHTML('<html><body>' . $this->getContent() . '</body></html>');
        $xml = simplexml_import_dom($dom);
        $tag = $xml->xpath('body/*');
        foreach ($tag[0]->attributes() as $key => $value) {
            $this->attributes[$key] = $value->__toString();
        }
        unset($dom);
    }

    /**
     * @inheridoc
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->getAttributes());
    }

    /**
     * @param array $attributes
     * @return string
     */
    public function getContentWithUpdatedAttribute(array $attributes): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $this->getContent() . '</body></html>');
        $xpath = new \DOMXpath($dom);
        $tagList = $xpath->query('/html/body/*');
        $tag = $tagList->item(0);
        foreach ($attributes as $key => $value) {
            if (null === $value && isset($tag)) {
                $tag->removeAttribute($key);
                continue;
            }

            $tag->setAttribute($key, $value);
        }

        return $dom->saveHTML($tag);
    }
}
