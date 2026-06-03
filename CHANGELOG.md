# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
