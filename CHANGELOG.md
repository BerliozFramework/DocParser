# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.2.1] - 2026-06-02

### Fixed

- Fix page resolution failing for paths containing spaces (or other special characters): `Page::getPath()` now returns a canonical decoded path, `FileSet::normalizePath()` decodes incoming paths, and URL encoding is applied only when writing `href`/`src` attributes in `PathTreatment`

## [2.2.0] - 2026-05-13

### Added

- `DoctrineRst` parser based on `doctrine/rst-parser` as replacement for the deprecated `reStructuredText` parser
- Optional `$errorHandler` callback to `DocCacheGenerator` for cache read failures instead of silently swallowing exceptions

### Changed

- Improve docblocks: add `@var Entry[]` on `EntryIterable::$entries`, document `FileSet::normalizePath()` behavior
- Rename `PageSummaryTreatment::getHeaderLevel()` to `getSummaryDepth()` with clarified docblock

### Deprecated

- `reStructuredText` parser and associated `RST\IndexDirective`, `RST\IndexNode` classes; relies on the unmaintained `gregwar/rst` package — use `DoctrineRst` parser instead

### Removed

- Dead code in `Entry::isVisible()` (null check on a bool property)
- Redundant `in_array` host check in `ExternalLinkTreatment::isExternalLink()`
- Unused `$ids` variable in `PageSummaryTreatment::makePageSummary()`

### Fixed

- Fix `DocGenerator::parserAcceptFile()` calling `acceptExtension()` instead of `acceptMime()` for MIME type fallback
- Fix `DocIntegrity::check()` silently dropping errors from subsequent pages due to `+=` on numeric arrays
- Fix `DocGenerator::getConfig()` mutating internal config array when returning default values (`??=` replaced by `??`)
- Fix `ExternalLinkTreatment::doExternalLinksTreatment()` calling `setContents()` inside the loop instead of once after
- Fix `PageSummaryTreatment::makePageSummary()` not deduplicating pre-existing duplicate header ids
- Fix `DocSummary::addPage()` crashing with `TypeError` when `summary-order` meta contains non-scalar values
- Fix `Page::getPath()` not encoding directory segments (only slug was encoded)
- Fix `RawFile::setContents()` not rewinding stream after `ftruncate`, causing NUL-byte corruption
- Fix `DocGenerator` not sorting parsers and treatments by priority before execution

### Security

- Bump minimum phpunit version to ^9.6.34

## [2.1.0] - 2026-02-20

### Added

- Compatibility with `berlioz/http-message` ^3.0
- Compatibility with `berlioz/http-selector` ^3.0
- Compatibility with `league/flysystem` ^3.0

## [2.0.0] - 2025-09-16

### Added

- `DocCacheGenerator` accept config for write operations of filesystem
- `DocSummary::setActive()` and `DocSummary::getActive()` methods
- `Entry::getPrev()` and `Entry::getNext()` methods to get siblings

### Changed

- Markdown parser use `FrontMatterExtension` of `league/commonmark` package to get metadata
- Markdown parser accept `ConverterInterface` instead of `EnvironmentInterface`

## [2.0.0-beta3] - 2022-05-17

### Added

- Prefix path option for cache generator

### Changed

- Use `FilesystemOperator` interface instead of `Filesystem` class
- Parser can return NULL if it does not accept file
- `DocGenerator` accept multiple parser

## [2.0.0-beta2] - 2022-03-21

### Changed

- Uses path resolution from `berlioz/helpers` package instead of internal functions
- Minimum compatibility version with `league/commonmark` to  2.2

### Fixed

- Anchors not kept in path resolution

## [2.0.0-beta1] - 2021-09-03

### Changed

- Bump compatibility version with PHP to ^8.0
- Bump compatibility version with `league/commonmark` to ^2.0
- Bump compatibility version with `berlioz/html-selector` to ^2.0
- Bump compatibility version with `berlioz/http-message` to ^2.0
- Strict types
- PHP 8 refactoring

## [1.3.0] - 2021-09-02

### Changed

- Signature of `EntryIterable::getIterator(): ArrayIterator` for PHP 8.1
- Signature of `FileSet::getIterator(): ArrayIterator` for PHP 8.1
- Signature of `FileSet::count(): int` for PHP 8.1

### Fixed

- Tests with date time zone

## [1.2.1] - 2021-06-08

### Changed

- Allow `berlioz/http-message` version 2

### Fixed

- Minimum compatibility for production

## [1.2.0] - 2021-06-01

### Changed

- Bump minimum compatibility of `league/commonmark` to 1.6

## [1.1.1] - 2021-04-30

### Fixed

- Cast order of summary to string to allow nested orders

## [1.1.0] - 2021-04-30

### Changed

- Cast value of metas into boolean, integer and float if necessary

## [1.0.0] - 2020-12-01

Stable version

## [0.2-alpha] - 2020-11-05

Refactoring

## [0.1-alpha] - 2020-04-27

Initial version
