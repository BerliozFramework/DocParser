# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning](http://semver.org/). For change log format, use [Keep a Changelog](http://keepachangelog.com/).

## [v2.0.0-beta4] - 2022-05-17

### Changed

- Markdown parser use `FrontMatterExtension` of `league/commonmark` package to get metadata
- Markdown parser accept `ConverterInterface` instead of `EnvironmentInterface`

## [v2.0.0-beta3] - 2022-05-17

### Added

- Prefix path option for cache generator

### Changed

- Use `FilesystemOperator` interface instead of `Filesystem` class
- Parser can return NULL if it does not accept file
- `DocGenerator` accept multiple parser

## [v2.0.0-beta2] - 2022-03-21

### Changed

- Uses path resolution from `berlioz/helpers` package instead of internal functions
- Minimum compatibility version with `league/commonmark` to  2.2

### Fixed

- Anchors not kept in path resolution

## [v2.0.0-beta1] - 2021-09-03

### Changed

- Bump compatibility version with PHP to ^8.0
- Bump compatibility version with `league/commonmark` to ^2.0
- Bump compatibility version with `berlioz/html-selector` to ^2.0
- Bump compatibility version with `berlioz/http-message` to ^2.0
- Strict types
- PHP 8 refactoring

## [v1.3.0] - 2021-09-02

### Changed

- Signature of `EntryIterable::getIterator(): ArrayIterator` for PHP 8.1
- Signature of `FileSet::getIterator(): ArrayIterator` for PHP 8.1
- Signature of `FileSet::count(): int` for PHP 8.1

### Fixed

- Tests with date time zone

## [v1.2.1] - 2021-06-08

### Changed

- Allow `berlioz/http-message` version 2

### Fixed

- Minimum compatibility for production

## [v1.2.0] - 2021-06-01

### Changed

- Bump minimum compatibility of `league/commonmark` to 1.6

## [v1.1.1] - 2021-04-30

### Fixed

- Cast order of summary to string to allow nested orders

## [v1.1.0] - 2021-04-30

### Changed

- Cast value of metas into boolean, integer and float if necessary

## [v1.0.0] - 2020-12-01

Stable version

## [v0.2-alpha] - 2020-11-05

Refactoring

## [v0.1-alpha] - 2020-04-27

Initial version
