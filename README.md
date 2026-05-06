# Magento 2 Page Speed

[![Latest Stable Version](https://poser.pugx.org/hryvinskyi/magento2-page-speed/v/stable)](https://packagist.org/packages/hryvinskyi/magento2-page-speed)
[![Total Downloads](https://poser.pugx.org/hryvinskyi/magento2-page-speed/downloads)](https://packagist.org/packages/hryvinskyi/magento2-page-speed)
[![PayPal donate button](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=volodymyr%40hryvinskyi%2ecom&lc=UA&item_name=Magento%202%20Page%20Speed&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted "Donate once-off to this project using Paypal")
[![Latest Unstable Version](https://poser.pugx.org/hryvinskyi/magento2-page-speed/v/unstable)](https://packagist.org/packages/hryvinskyi/magento2-page-speed)
[![License](https://poser.pugx.org/hryvinskyi/magento2-page-speed/license)](https://packagist.org/packages/hryvinskyi/magento2-page-speed)

## Description

Core implementation of Page Speed utilities and helpers. Provides concrete implementations of the PageSpeedApi interfaces with intelligent file merging, caching, and HTML manipulation capabilities.

## Features

### Intelligent File Merging
- **Changelog-Based Merging** - Only merges files when source files are newer than target
- **AbstractMerger** - Base class for implementing file merge operations
- **Extension Filtering** - Filter files by extension during merge
- **Before-Merge Callbacks** - Transform content before merging

### URL & File Management
- **GetLocalPathFromUrl** - Converts absolute URLs to local filesystem paths
- **GetFileContentByUrl** - Fetches file content from URLs
- **IsInternalUrl** - Validates if URLs are internal to the domain
- **Path Normalization** - Handles various URL formats and edge cases

### HTML Parsing & Manipulation
- **Finder\Js** - Find JavaScript resources in HTML
- **Finder\Css** - Find CSS resources in HTML
- **Finder\HtmlComment** - Find HTML comments
- **Html\ReplaceIntoHtml** - Replace content within HTML
- **Html\InsertStringBeforeBodyEnd** - Insert before `</body>` tag
- **Html\InsertStringBeforeHeadEnd** - Insert before `</head>` tag
- **Html\GetContent** - Extract content from HTML

### Cache Management
- **Cache** - Implementation for storing optimization artifacts
- **Size Limits** - Configurable cache folder size limits
- **Efficient Storage** - Only stores what's necessary

## Installation

```bash
composer require hryvinskyi/magento2-page-speed
bin/magento module:enable Hryvinskyi_PageSpeed
bin/magento setup:upgrade
bin/magento cache:flush
```

## Technical Details

### AbstractMerger Class

Base class for all file merging operations with intelligent changelog detection:

```php
abstract class AbstractMerger
{
    /**
     * Merge files only if source files are newer than target
     *
     * @param array $files Files to merge
     * @param string $targetPath Output file path
     * @param callable|null $beforeMergeCallback Optional content transformation
     * @return bool Success status
     */
    public function merge(array $files, string $targetPath, ?callable $beforeMergeCallback = null): bool
    {
        // Implementation includes:
        // - Changelog detection (checks file modification times)
        // - Content transformation via callback
        // - Atomic file writing
        // - Error handling
    }
}
```

**Key Features:**
- Only merges when source files have changed (performance optimization)
- Supports callbacks for content transformation before merging
- Handles file locking and atomic writes
- Extensible via inheritance

### Cache Implementation

Provides efficient caching for optimization artifacts:

```php
class Cache implements CacheInterface
{
    /**
     * Store content in cache
     *
     * @param string $key Cache key
     * @param string $content Content to cache
     * @return bool Success status
     */
    public function set(string $key, string $content): bool;

    /**
     * Retrieve content from cache
     *
     * @param string $key Cache key
     * @return string|null Cached content or null if not found
     */
    public function get(string $key): ?string;
}
```

**Features:**
- Size limit enforcement
- Automatic cleanup of old entries
- Fast lookup and retrieval

### URL & Path Management

Handles complex URL-to-filesystem-path conversions:

```php
class GetLocalPathFromUrl implements GetLocalPathFromUrlInterface
{
    /**
     * Convert URL to local filesystem path
     *
     * @param string $url Absolute or relative URL
     * @return string|null Local filesystem path or null if not resolvable
     */
    public function execute(string $url): ?string
    {
        // Handles:
        // - Absolute URLs (https://example.com/file.js)
        // - Relative URLs (/file.js)
        // - Protocol-relative URLs (//example.com/file.js)
        // - Media URLs, static URLs, pub URLs
        // - Validates internal vs external URLs
    }
}
```

### HTML Finders

Efficient HTML parsing to locate resources:

```php
class Js implements JsInterface
{
    /**
     * Find all JavaScript tags in HTML
     *
     * @param string $html HTML content
     * @return array Array of script tag information
     */
    public function findAll(string $html): array;
}

class Css implements CssInterface
{
    /**
     * Find all CSS link/style tags in HTML
     *
     * @param string $html HTML content
     * @return array Array of CSS tag information
     */
    public function findAll(string $html): array;
}
```

**Capabilities:**
- Regex-based HTML parsing
- Handles inline and external resources
- Preserves tag attributes
- Returns structured data for processing

## Dependencies

- `magento/framework: *`
- `hryvinskyi/magento2-page-speed-api: 1.0.*`
- `ext-dom: *`
- `ext-simplexml: *`

## Use Cases

This module is used by:
- CSS optimization modules for merging CSS files
- JavaScript optimization modules for merging JS files
- Lazy loading modules for HTML manipulation
- Any module requiring file merge or HTML parsing capabilities

## Compatibility

- Magento 2.3.x
- Magento 2.4.x
- PHP 7.4+
- PHP 8.0+
- PHP 8.1+

## Support

For issues, questions, or contributions:
- Email: volodymyr@hryvinskyi.com
- GitHub: https://github.com/hryvinskyi

## License

[MIT License](LICENSE)

## Author

**Volodymyr Hryvinskyi**
- Email: volodymyr@hryvinskyi.com
