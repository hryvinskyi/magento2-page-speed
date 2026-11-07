# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.8] - 2026-11-07

### Changed
- Requires `hryvinskyi/magento2-base` `^3.0`, which the package used without
  declaring it; `Hryvinskyi_Base` joins the `module.xml` sequence.
- Requires `magento/module-store`, which `Model\IsInternalUrl` used without
  declaring it; `Magento_Store` joins the `module.xml` sequence.
- `Model\Finder\Result\Tag` renders attributes through the injected
  `Hryvinskyi\Base\Api\Html\AttributeRendererInterface`, so a tag is created
  through the generated `TagInterfaceFactory`, as the finders do; `new Tag()` no
  longer works.
- `Model\Finder\Result\Tag`, `Model\Finder\Js` and `Model\GetContentFromTag`
  take the new `Api\Html\OpeningTagParserInterface` from the container, which is
  the one answer to where an opening tag ends; the expression of `Model\Finder\Css`
  is built on the same text. `Model\GetContentFromTag` reads through the generated
  `\DOMDocumentFactory`.
- `Tag::getContentWithUpdatedAttribute()` is now an edit of the opening tag instead
  of a re-render of all its attributes. Attributes the update does not name keep
  their exact bytes: quotes, order, whitespace, entities, boolean attributes as
  written and a self-closing ` />`. A replaced attribute is rendered where it
  stands, a removed one goes with the whitespace before it, and a new one follows
  the last attribute. Output is therefore no longer normalised: code that relied
  on the attribute order, on `defer` becoming `defer="defer"` or on double quotes
  must stop doing so.
- `Tag::getContentWithUpdatedAttribute()` takes a value as plain text or as the
  HTML spells it. `&amp;`, `&lt;`, `&gt;`, `&quot;` and `&#039;` are decoded before
  the value is encoded, so each stays one entity; any other entity is text.

### Fixed
- An update no longer corrupts attributes it was not asked to change:
  `&amp;`, `&quot;`, `&eacute;`, `&nbsp;`, `&#8217;` and `&apos;` were encoded a
  second time, `src="a.jpg?w=1&amp;h=2"` becoming `src="a.jpg?w=1&amp;amp;h=2"`.
- A `>` inside a quoted attribute value no longer ends the opening tag, so
  `alt="a>b"` is read whole by `getAttributes()` and by an update.
- Attribute names may contain `:`, `@`, `.`, `-` and `_`. `x-on:click`, `@click`
  and `xlink:href` used to be split or stripped, in `getAttributes()` as well as in
  an update. `getAttributes()` still returns values as the HTML spells them.
- A tag written without a space before its slash, such as `<br/>`, is recognised.
- A value read from `getAttributes()` and written to another attribute is encoded
  once: `href="x.css?a=1&amp;b=2"` no longer comes out as
  `data-lazy-source="x.css?a=1&amp;amp;b=2"`, which a loader requested with a
  parameter named `amp;b`.
- An update matches attribute names whatever their case, as HTML does. `TYPE` used
  to stay in place with a new `type` after it, and the browser read the first one,
  so a script meant to wait ran eagerly.
- `getAttributes()` and `hasAttribute()` read names in lower case, so `SRC` counts as
  `src`. An external script written in upper case was taken for an inline one.
  A reader comparing these names with a list of its own has to compare in lower case.
- A script's type is read from its `type` attribute alone, not from an attribute ending
  in it, such as `data-type`, or from a value holding the word, such as `a.js?type=module`.
- A stylesheet link with a `>` inside a quoted value, such as
  `media="(width>=768px)"`, is found whole; it used to be cut at that `>`, so an
  update wrote inside the value and the stylesheet never loaded. `rel` is only read
  outside quoted values, as a name of its own, so `data-rel="stylesheet"` no longer
  makes a link a stylesheet.
- `Model\Finder\Js` reads a `type` that follows a `>` inside a quoted value, so such
  a JSON block no longer counts as JavaScript.
- `Model\GetContentFromTag` no longer returns part of the opening tag for an element
  with a `>` inside a quoted value, which the merger wrote into the bundle, and
  returns an empty string instead of failing when the text holds no element.

### Removed
- An unused import in `Model\Finder\Css` that named a package this module does
  not require.

## [1.0.7] - 2026-06-03

### Fixed
- PHP 8.4/8.5 compatibility: explicitly mark nullable parameters across the
  finder and merger classes.
  - `Model\Finder\AbstractRegexp::findAll()` and `findByNeedle()` now declare
    `?int $start` / `?int $end`.
  - `Model\Finder\Js::findAll()`, `findInline()`, `findExternal()` now declare
    `?int $start` / `?int $end`.
  - `Model\Finder\Css::findAll()` now declares `?int $start` / `?int $end`.
  - `Model\AbstractMerger::mergeFiles()` now declares
    `?\Closure $beforeMergeCallback`.

  Resolves the *"Implicitly marking parameter as nullable is deprecated, the
  explicit nullable type must be used instead"* deprecation notices.
