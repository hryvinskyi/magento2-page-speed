<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeedApi\Api\GetContentFromTagInterface;

class GetContentFromTag implements GetContentFromTagInterface
{
    /**
     * @inheridoc
     */
    public function execute(string $tagHtml): string
    {
        $pattern = "/^<[^>]+>(.*)<[^<]+>$/is";
        $result = preg_match($pattern, $tagHtml, $matches);
        if (!$result || !array_key_exists(1, $matches)) {
            $dom = new \DOMDocument();
            @$dom->loadHTML(
                '<?xml encoding="utf-8" ?>'
                . '<html><body>' . $tagHtml . '</body></html>'
            );
            $xml = simplexml_import_dom($dom);
            $tag = $xml->xpath('/html/body/*');
            $tag = $tag[0];
            return $tag->__toString();
        }
        return $matches[1];
    }
}
