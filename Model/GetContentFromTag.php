<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model;

use Hryvinskyi\PageSpeed\Api\Html\OpeningTagParserInterface;
use Hryvinskyi\PageSpeedApi\Api\GetContentFromTagInterface;

class GetContentFromTag implements GetContentFromTagInterface
{
    /**
     * @param OpeningTagParserInterface $openingTagParser finds where the opening tag of the element ends
     * @param \DOMDocumentFactory $documentFactory creates the document that reads an element without a closing tag
     */
    public function __construct(
        private readonly OpeningTagParserInterface $openingTagParser,
        private readonly \DOMDocumentFactory $documentFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(string $tagHtml): string
    {
        $openingTag = $this->openingTagParser->parse($tagHtml);

        if ($openingTag !== null && preg_match('/^(.*)<[^<]+>$/s', $openingTag['rest'], $matches) === 1) {
            return $matches[1];
        }

        return $this->readFromDocument($tagHtml);
    }

    /**
     * Reads the text of an element the HTML parser has to repair, such as one without a closing tag.
     *
     * The parser's complaints about such markup are expected, so they are collected and dropped rather than reported.
     *
     * @param string $tagHtml the element's text
     * @return string the element's own text, or an empty string when the markup holds no element
     */
    private function readFromDocument(string $tagHtml): string
    {
        $document = $this->documentFactory->create();
        $usedInternalErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $tagHtml . '</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($usedInternalErrors);

        $elements = simplexml_import_dom($document)?->xpath('/html/body/*');

        return is_array($elements) && $elements !== [] ? (string)$elements[0] : '';
    }
}
