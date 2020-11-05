# Berlioz Doc Parser

## Installation

### Composer

You can install **Berlioz Doc Parser** with [Composer](https://getcomposer.org/), it's the recommended installation.

```bash
$ composer require berlioz/doc-parser
```

### Dependencies

- **PHP** ^7.4 || ^8.0
- PHP libraries:
  - **mbstring**
- Packages:
  - **berlioz/html-selector**
  - **berlioz/http-message**
  - **league/flysystem**

To parse files, you need additional package:

- For MarkDown files: `league/commonmark`
- For reStructuredText files: `gregwar/rst`

## Usage

Library use `league/flysystem` library to manipulate files.
This library permit to use some adapter like **Local files**, **GitLab**, **Google**... that's very useful for documentation source.

### Documentation generation

You need to define the parser of you documentation, in the example: `Berlioz\DocParser\Parser\Markdown`.

```php
use Berlioz\DocParser\DocGenerator;
use Berlioz\DocParser\Parser\Markdown;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

$version = '1.0';

$docGenerator = new DocGenerator(new Markdown());
$documentation =
    $docGenerator->handle(
        $version,
        new Filesystem(new LocalFilesystemAdapter('/.../doc'))
    );
```

### Documentation files

You can access to documentation files easily:

```php
/** @var \Berlioz\DocParser\Doc\Documentation $documentation */
$files = $documentation->getFiles();
```

And filter the files by type:

```php
use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\Page;

/** @var \Berlioz\DocParser\Doc\Documentation $documentation */
$pages = $documentation->getFiles(fn(FileInterface $file) => $file instanceof Page);
```

Two file type exists:

- `Berlioz\DocParser\Doc\File\Page`: page with parsed content
- `Berlioz\DocParser\Doc\File\RawFile`: raw file like images

### Documentation handle

When your documentation is generate, you can use `Documentation::handle()` method to get pages and files.

```php
use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\Page;

/** @var Documentation $documentation */
$file = $documentation->handle('path/of/my/page');

if (null === $file) {
   // Show not found error
}

// Raw file?
if (!$file instanceof Page) {
    // Return \Psr\Http\Message\ResponseInterface object
    return $file->response();
}

// Show HTML content of my page
echo $file->getContents();
```

### Treatments

A concept of treatment is implement to manipulate files after generation.

Some treatments are activate by default:

- `Berlioz\DocParser\Treatment\DocSummaryTreatment`: generate the summary of documentation
- `Berlioz\DocParser\Treatment\PageSummaryTreatment`: generate the pages summaries
- `Berlioz\DocParser\Treatment\PathTreatment`: resolve all links to transform in relative links
- `Berlioz\DocParser\Treatment\TitleTreatment`: extract title of page and set to `Page` object

Optionals treatments are available:

- `Berlioz\DocParser\Treatment\BootstrapTreatment`: add CSS classes to HTML elements to beautify them for Bootstrap framework

You can also create your treatment class and add this to generator:

```php
use Berlioz\DocParser\DocGenerator;
use Berlioz\DocParser\Treatment\BootstrapTreatment;

/** @var DocGenerator $docGenerator */
$docGenerator->addTreatment(new BootstrapTreatment($docGenerator));
```

### Formats

#### MarkDown

A specific tag is for indexation of documentation.

    ```index
    title: Title of page
    slug: Slug name (replace only the filename, not path)
    breadcrumb: Category; Sub category; My page
    summary-order: 1
    summary-visible: true
    ```

#### reStructuredText

A specific directive is for indexation of documentation.

    ...index:
        :title: Title of page
        :slug: Slug name (replace only the filename, not path)
        :breadcrumb: Category; Sub category; My page
        :summary-order: 1
        :summary-visible: true

#### Options available for indexation

- `title`: replace the title of page, the title treatment does not replace this title
- `slug`: replace the filename of page, not the complete path, it's an url encoded representation of value
- `breadcrumb`: the breadcrumb of page into the documentation summary, if not present, the page will not in the summary
- `summary-order`: the order in summary section
- `summary-visible`: default to true, but you can define the page to not visible in summary

You can define your own options, for your treatments for example, options are available with `Page::getMeta(...)` method.

### Summary

#### Doc summary

Doc summary references all pages with a breadcrumb.
It's a hierarchy of `Berlioz\DocParser\Doc\Summary\Entry` objects.

You can get path of entry with method `Entry::getPath()`, it's the absolute path from root directory of documentation.

#### Page summary

Page summary references all headings in the page content.
It's a hierarchy of `Berlioz\DocParser\Doc\Summary\Entry` objects.

No path given to the entry, but an id `Entry::getId()` linked to the corresponding heading.

### Parser

Two parser are available by default:

- `Berlioz\DocParser\Parser\Markdown`: to parse MarkDown files, use `league/commonmark` package
- `Berlioz\DocParser\Parser\reStructuredText`: to parse reStructuredText files, use `gregwar/rst` package

You can also create your own parser, you need only to implement `Berlioz\DocParser\Parser\ParserInterface` interface.

If you need to add an extension to a specific parser, a getter method is available to access to the original parser. Example for MarkDown parser: `\Berlioz\DocParser\Parser\Markdown::getCommonMarkConverter()`.

You can also pass to the `Markdown` and `reStructuredText` constructors the same parameters that corresponding libraries.

### Cache

Generation of documentation take time, and it's not acceptable to generate documentation at every request.

`Berlioz\DocParser\DocCacheGenerator` class generate a cache of generated and treated documentation to reuse it quickly.
The cache use `league/flysystem` package to store cache files.

Example of usage:

```php
use Berlioz\DocParser\DocCacheGenerator;
use Berlioz\DocParser\DocGenerator;
use Berlioz\DocParser\Parser\Markdown;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

$version = '1.0';
$cacheFilesystem = new Filesystem(new LocalFilesystemAdapter('/.../cache'));
$docCacheGenerator = new DocCacheGenerator($cacheFilesystem);

if (null === ($documentation = $docCacheGenerator->get($version))) {
    $docGenerator = new DocGenerator(new Markdown());
    $documentation =
        $docGenerator->handle(
            $version,
            new Filesystem(new LocalFilesystemAdapter('/.../doc'))
        );

    $docCacheGenerator->save($documentation);
}

$documentation->handle('path/of/my/page');
```