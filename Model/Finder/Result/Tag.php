<?php
/**
 * Copyright (c) 2022-2026. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Finder\Result;

use Hryvinskyi\Base\Api\Html\AttributeRendererInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\PageSpeed\Api\Html\OpeningTagParserInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;

/**
 * An HTML element found in a page, with the attributes of its opening tag.
 *
 * A tag takes its collaborators from the container, so it is created through the generated factory of its interface.
 */
class Tag extends Raw implements TagInterface
{
    /**
     * Attributes of the opening tag, by lower-case name, with their values as the HTML spells them.
     *
     * @var array<string, string>
     */
    private array $attributes = [];

    /**
     * @param AttributeRendererInterface $attributeRenderer renders the attributes an update sets
     * @param HtmlEncoderInterface $htmlEncoder decodes the values of an update before they are rendered
     * @param OpeningTagParserInterface $openingTagParser finds the opening tag of the element
     */
    public function __construct(
        private readonly AttributeRendererInterface $attributeRenderer,
        private readonly HtmlEncoderInterface $htmlEncoder,
        private readonly OpeningTagParserInterface $openingTagParser
    ) {
    }

    /**
     * Returns the attributes of the opening tag.
     *
     * A name is in lower case, because HTML reads attribute names whatever their case and every reader asks for
     * `src` or `href`. A value stays as the HTML spells it, its entities not decoded.
     *
     * @return array<string, string> attribute name => value; a boolean attribute has its own name as value
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function setContent(string $content): void
    {
        parent::setContent($content);

        $this->attributes = $this->parseAttributesFromOpeningTag($content);
    }

    /**
     * @inheritdoc
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists(strtolower($key), $this->getAttributes());
    }

    /**
     * Edit the opening tag in place, without parsing inner content.
     *
     * Only the named attributes change: everything else in the tag keeps its exact bytes, so an attribute's quotes,
     * entities and position survive an update of another one. A replaced attribute is rendered where it stands, a
     * removed one goes with the whitespace before it, and a new one follows the last attribute of the tag, which
     * keeps it ahead of a self-closing slash. Every occurrence of a repeated attribute is replaced or removed.
     *
     * Names match whatever their case, as HTML reads them; a replacement takes the name as the update spells it.
     *
     * @param array<string,string|null> $attributes Attributes to set or remove (null value removes the attribute).
     *                                              A value is plain text or a value as the HTML spells it, such as
     *                                              one read from getAttributes(): `&amp;`, `&lt;`, `&gt;`, `&quot;`
     *                                              and `&#039;` in it are decoded first, so each stays one entity once
     *                                              the renderer encodes the value. Any other entity reaches the
     *                                              renderer as text.
     *
     * @return string The full tag content with updated attributes.
     * @throws InvalidParamException When a value cannot be rendered as text.
     */
    public function getContentWithUpdatedAttribute(array $attributes): string
    {
        $content = $this->getContent();
        $openingTag = $this->openingTagParser->parse($content);

        if ($openingTag === null) {
            return $content;
        }

        return '<' . $openingTag['name']
            . $this->updateAttributeString($openingTag['attributes'], $this->indexChanges($attributes))
            . '>'
            . $openingTag['rest'];
    }

    /**
     * Prepares an update: keys it by the lower-case name and decodes its values.
     *
     * @param array<string,string|null> $attributes Attributes to set or remove.
     *
     * @return array<string,array{name:string,value:string|null}> The changes, by lower-case attribute name.
     */
    private function indexChanges(array $attributes): array
    {
        $changes = [];
        foreach ($attributes as $name => $value) {
            $changes[strtolower($name)] = [
                'name' => $name,
                'value' => is_string($value) ? $this->htmlEncoder->decode($value) : $value,
            ];
        }

        return $changes;
    }

    /**
     * Applies the changes to the attribute portion of an opening tag, leaving everything else as it was written.
     *
     * @param string $attrString The attribute portion of an opening tag.
     * @param array<string,array{name:string,value:string|null}> $changes Attributes to set or remove, by lower-case
     *                                                                    name.
     *
     * @return string The updated attribute portion.
     * @throws InvalidParamException When a value cannot be rendered as text.
     */
    private function updateAttributeString(string $attrString, array $changes): string
    {
        $updated = '';
        $position = 0;
        $existing = [];

        foreach ($this->openingTagParser->findAttributes($attrString) as $attribute) {
            $key = strtolower($attribute['name']);
            $existing[$key] = true;
            $updated .= substr($attrString, $position, $attribute['start'] - $position)
                . $this->updateAttribute($attribute, $changes[$key] ?? null);
            $position = $attribute['start'] + strlen($attribute['source']);
        }

        return $updated
            . $this->attributeRenderer->render(array_column(array_diff_key($changes, $existing), 'value', 'name'))
            . substr($attrString, $position);
    }

    /**
     * Returns what stands in the tag for one attribute after the changes.
     *
     * @param array{name:string,value:string,whitespace:string,source:string,start:int} $attribute As found in the tag.
     * @param array{name:string,value:string|null}|null $change The change of this attribute, if the update names it.
     *
     * @return string The attribute as written, its rendered replacement, or nothing when it is removed.
     * @throws InvalidParamException When a value cannot be rendered as text.
     */
    private function updateAttribute(array $attribute, ?array $change): string
    {
        if ($change === null) {
            return $attribute['source'];
        }

        if ($change['value'] === null) {
            return '';
        }

        return $attribute['whitespace']
            . ltrim($this->attributeRenderer->render([$change['name'] => $change['value']]));
    }

    /**
     * Parse attributes from the opening tag of an HTML element string.
     *
     * @param string $content The full HTML element string.
     *
     * @return array<string, string> Parsed attributes, by lower-case name.
     */
    private function parseAttributesFromOpeningTag(string $content): array
    {
        $openingTag = $this->openingTagParser->parse($content);

        if ($openingTag === null) {
            return [];
        }

        $attributes = [];
        foreach ($this->openingTagParser->findAttributes($openingTag['attributes']) as $attribute) {
            $attributes[strtolower($attribute['name'])] = $attribute['value'];
        }

        return $attributes;
    }
}
