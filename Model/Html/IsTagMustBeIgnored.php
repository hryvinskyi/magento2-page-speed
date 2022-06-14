<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Html;

use Hryvinskyi\PageSpeedApi\Api\Html\IsTagMustBeIgnoredInterface;

class IsTagMustBeIgnored implements IsTagMustBeIgnoredInterface
{
    /**
     * @inheridoc
     */
    public function execute(string $tagHtml, array $ignoreTagList = [], array $anchorList = []): bool
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<html><body>' . $tagHtml . '</body></html>');
        $xml = simplexml_import_dom($dom);
        $tag = $xml->xpath('/html/body/*');
        $tag = $tag[0];
        $attributes = array();
        foreach ($tag->attributes() as $key => $value) {
            $attributes[$key] = $value->__toString();
        }
        if (count(array_intersect(array_keys($attributes), $ignoreTagList)) > 0) {
            return true;
        }
        $haystack = null;
        switch (strtolower($tag->getName())) {
            case 'script':
                $haystack = $tag->__toString();
                if (array_key_exists('src', $attributes)) {
                    $haystack = $attributes['src'];
                }
                break;
            case 'link':
                if (array_key_exists('href', $attributes)) {
                    $haystack = $attributes['href'];
                }
                break;
            case 'style':
                $haystack = $tag->__toString();
                break;
        }
        if ($haystack === null) {
            return false;
        }

        foreach ($anchorList as $anchor) {
            if (strpos($haystack, $anchor) !== false) {
                return true;
            }
        }

        return false;
    }
}
