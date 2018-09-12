<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\DocParser;

use Berlioz\DocParser\Documentation\DocumentationVersion;
use Berlioz\DocParser\Exception\GeneratorException;
use Berlioz\DocParser\File\FileInterface;
use Berlioz\DocParser\File\Page;
use Berlioz\DocParser\Loader\LoaderInterface;
use Berlioz\DocParser\Parser\Markdown;
use Berlioz\DocParser\Parser\ParserInterface;
use Berlioz\DocParser\Parser\reStructuredText;
use Berlioz\DocParser\Summary\Entry;
use Berlioz\HtmlSelector\Query;

class Generator
{
    /** @var array Options */
    private $options;
    /** @var \Berlioz\DocParser\Loader\LoaderInterface Loader */
    private $loader;
    /** @var \Berlioz\DocParser\Parser\ParserInterface[] Parsers */
    private $parsers = [];

    /**
     * Generator constructor.
     *
     * @param array $options Options
     */
    public function __construct(array $options)
    {
        $this->options = array_replace_recursive([], $options);

        $this->addParser(new Markdown());
        $this->addParser(new reStructuredText());
    }

    /**
     * Get option.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    //////////////
    /// LOADER ///
    //////////////

    /**
     * Get loader.
     *
     * @return \Berlioz\DocParser\Loader\LoaderInterface|null
     */
    public function getLoader(): ?LoaderInterface
    {
        return $this->loader;
    }

    /**
     * Set loader.
     *
     * @param \Berlioz\DocParser\Loader\LoaderInterface|null $loader
     *
     * @return \Berlioz\DocParser\Generator
     */
    public function setLoader(?LoaderInterface $loader): Generator
    {
        $this->loader = $loader;

        return $this;
    }

    ///////////////
    /// PARSERS ///
    ///////////////

    /**
     * Add parser.
     *
     * @param \Berlioz\DocParser\Parser\ParserInterface $parser
     */
    public function addParser(ParserInterface $parser)
    {
        $this->parsers[] = $parser;
    }

    /**
     * Get parser for a given file.
     *
     * @param \Berlioz\DocParser\File\FileInterface $file
     *
     * @return \Berlioz\DocParser\Parser\ParserInterface|null
     */
    public function getParserFor(FileInterface $file): ?ParserInterface
    {
        $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

        foreach ($this->parsers as $parser) {
            if ($parser->acceptMime($file->getMime()) || $parser->acceptExtension($extension)) {
                return $parser;
            }
        }

        return null;
    }

    //////////////
    /// HANDLE ///
    //////////////

    /**
     * Handle.
     *
     * @param \Berlioz\DocParser\Loader\LoaderInterface $loader Loader
     *
     * @return \Berlioz\DocParser\Documentation
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function handle(LoaderInterface $loader): Documentation
    {
        $this->setLoader($loader);
        $documentation = $this->loadDocumentation();

        return $documentation;
    }

    /**
     * Load documentations pages from loaders.
     *
     * @return \Berlioz\DocParser\Documentation
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    protected function loadDocumentation(): Documentation
    {
        $documentation = new Documentation($this->loader->getUniqid());

        foreach ($this->getLoader()->getVersions() as $version) {
            $documentation->addVersion($documentationVersion = new DocumentationVersion($version));

            foreach ($this->getLoader()->getFiles($version) as $filename => $file) {
                if (!is_null($parser = $this->getParserFor($file))) {
                    // Parse file and create page
                    $file = $parser->parse($file);
                }

                $documentationVersion->getFiles()->addFile($file);
            }

            $this->documentationTreatment($documentationVersion);
        }

        return $documentation;
    }

    /**
     * Documentation treatment.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentation
     *
     * @throws \Berlioz\DocParser\Exception\GeneratorException
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    protected function documentationTreatment(DocumentationVersion $documentation)
    {
        /** @var \Berlioz\DocParser\File\Page $page */
        foreach ($documentation->getFiles()->getFiles(Page::class) as $page) {
            try {
                $this->pageTreatment($documentation, $page);
            } catch (\Throwable $e) {
                throw new GeneratorException(sprintf('Unable to do HTML treatment of page "%s"', $page->getFilename()), 0, $e);
            }

            $documentation->getSummary()->addPage($page);
        }
    }

    /**
     * Page treatment.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentation
     * @param \Berlioz\DocParser\File\Page                          $page
     *
     * @throws \Berlioz\DocParser\Exception\DocParserException
     * @throws \Berlioz\HtmlSelector\Exception\HtmlSelectorException
     */
    protected function pageTreatment(DocumentationVersion $documentation, Page $page)
    {
        $html = $page->getParsedContent();

        // Encoding
        $encoding = mb_detect_encoding($html) ?? 'ascii';
        $html = sprintf('<html><head><meta charset="%s"></head><body>%s</body></html>', $encoding, $html);

        // Load HTML in HtmlSelector library
        $queryHtml = Query::loadHtml($html);

        // Extract metas
        $this->extractMetas($page, $queryHtml);

        // Links
        $this->treatPageLinks($documentation, $page, $queryHtml);

        // Medias
        $this->treatPageMedias($documentation, $page, $queryHtml);

        // Remove H1
        if (count($title = $queryHtml->find('h1:first')) == 1) {
            if (empty($page->getTitle())) {
                $page->setTitle(trim($title->text()));
            }

            if ($this->getOption('parsing.remove-h1', false) === true) {
                $title->remove();
            }
        }

        // Summary
        $this->generatePageSummary($page, $queryHtml);

        // Set final parsed content
        $page->setParsedContent((string) $queryHtml->find('html > body')->html());
    }

    /**
     * Extract metas.
     *
     * @param \Berlioz\DocParser\File\Page $page
     * @param \Berlioz\HtmlSelector\Query  $queryHtml
     *
     * @throws \Berlioz\HtmlSelector\Exception\HtmlSelectorException
     */
    public function extractMetas(Page $page, Query $queryHtml)
    {
        $metas = $page->getMetas();

        foreach ($queryHtml->find('meta[name^="docparser-"]') as $metaEl) {
            $value = trim($metaEl->attr('content'));

            switch ($value) {
                case 'true':
                case 'false':
                    $value = ($value == 'true');
                    break;
            }

            $metas[trim(substr($metaEl->attr('name'), 10))] = $value;
            $metaEl->remove();
        }

        $page->setMetas($metas);
    }

    /**
     * Page links treatment.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentation
     * @param \Berlioz\DocParser\File\Page                          $page
     * @param \Berlioz\HtmlSelector\Query                           $queryHtml
     *
     * @throws \Berlioz\DocParser\Exception\DocParserException
     * @throws \Berlioz\HtmlSelector\Exception\HtmlSelectorException
     */
    protected function treatPageLinks(DocumentationVersion $documentation, Page $page, Query $queryHtml)
    {
        // Replacement of links
        foreach ($queryHtml->find('a[href]') as $link) {
            $href = $this->resolveRelativePath($page->getFilename(), $link->attr('href'));

            if ($href !== false) {
                if (is_null($pageLinked = $documentation->getFiles()->findByFilename($href))) {
                    throw new GeneratorException(sprintf('Link to "%s" broken in file "%s"', $link->attr('href'), $page->getFilename()));
                }

                $link->attr('href', $page->getRelativeUrlPathFor($pageLinked));
            }

            if ($href === false) {
                $host = parse_url($link->attr('href'), PHP_URL_HOST);

                if (empty($host)) {
                    throw new GeneratorException(sprintf('Unable to get link of "%s" in file "%s"', $link->attr('href'), $page->getFilename()));
                }

                $searchSubDomains = substr($host, 1) == '.';

                foreach ((array) $this->getOption('url.host') as $internalHost) {
                    $addExternal = (bool) $this->getOption('url.host_external_blank', true);

                    if ($host == $internalHost ||
                        ($searchSubDomains === true && $host == substr($internalHost, 1)) ||
                        ($searchSubDomains === true && mb_substr($host, -mb_strlen($internalHost)) == $internalHost)) {
                        $addExternal = false;
                    }

                    if ($addExternal) {
                        $link->attr('target', '_blank')
                             ->attr('rel', $this->getOption('url.host_external_rel', 'noopener'));
                    }
                }
            }
        }
    }

    /**
     * Treat page medias.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentation
     * @param \Berlioz\DocParser\File\Page                          $page
     * @param \Berlioz\HtmlSelector\Query                           $queryHtml
     *
     * @throws \Berlioz\DocParser\Exception\DocParserException
     * @throws \Berlioz\HtmlSelector\Exception\HtmlSelectorException
     */
    protected function treatPageMedias(DocumentationVersion $documentation, Page $page, Query $queryHtml)
    {
        foreach ($queryHtml->find('[src]') as $mediaEl) {
            $mediaElSrc = $mediaEl->attr('src');
            $href = $this->resolveRelativePath($page->getFilename(), $mediaElSrc);

            // Internal link
            if ($href !== false) {
                if (is_null($pageMedia = $documentation->getFiles()->findByFilename($href))) {
                    throw new GeneratorException(sprintf('Link to "%s" broken in file "%s"', $mediaEl->attr('href'), $page->getFilename()));
                }

                $mediaEl->attr('src', $page->getRelativeUrlPathFor($pageMedia));
            }
        }
    }

    /**
     * Extract document summary from Query.
     *
     * @param \Berlioz\DocParser\File\Page $page
     * @param \Berlioz\HtmlSelector\Query  $queryHtml
     *
     * @return void
     * @throws \Berlioz\HtmlSelector\Exception\HtmlSelectorException
     */
    private function generatePageSummary(Page $page, Query $queryHtml)
    {
        $summary = new Summary($this->getOption('parsing.summary_max_level'));
        $ids = [];
        $headers = $queryHtml->find(':header:not(h1)');

        // Function to treat id
        $prepareId =
            function (string $text): string {
                $id = preg_replace(['/[^\w\s\-]/i', '/\s+/', '/-{2,}/'], ['', '-', '-'], $text);
                $id = trim(mb_strtolower($id), '-');

                return $id;
            };

        $entries = [];
        foreach ($headers as $header) {
            try {
                // Header level
                if ($header->is('h3')) {
                    $headerLevel = 2;
                } elseif ($header->is('h4')) {
                    $headerLevel = 3;
                } elseif ($header->is('h5')) {
                    $headerLevel = 4;
                } elseif ($header->is('h6')) {
                    $headerLevel = 5;
                } else {
                    $headerLevel = 1;
                }

                // Remove old parent
                for ($i = count($entries) - 1; $i >= 0; $i--) {
                    if ($entries[$i]['level'] >= $headerLevel) {
                        array_pop($entries);
                    }
                }

                // Get id of header
                if (is_null($id = $header->attr('id')) || in_array($id, $ids)) {
                    if (is_null($id)) {
                        $id = '';
                        foreach ($entries as $entry) {
                            /** @var \Berlioz\DocParser\Summary\Entry $entry */
                            $entry = $entry['entry'];
                            $id .= $prepareId($entry->getTitle()) . '-';
                        }
                        $id .= $prepareId($header->text());
                    }

                    // Find new id
                    $idPattern = $id;
                    $i = 1;
                    while ($queryHtml->find(sprintf('[id="%s"]', $id))->count() > 0) {
                        $id = sprintf('%s-%d', $idPattern, $i);
                        $i++;
                    }

                    // Set new id to header
                    $header->attr('id', $id);
                }

                // Create summary entry
                $entry = new Entry();
                $entry->setTitle($header->text())
                      ->setUrl($page->getUrlPath())
                      ->setId($id)
                      ->setVisible($headerLevel <= $this->getOption('parsing.summary_max_level'));

                // Add entry to summary hierarchy
                {
                    if (($lastEntry = end($entries)) !== false) {
                        /** @var \Berlioz\DocParser\Summary\Entry $lastEntry */
                        $lastEntry = $lastEntry['entry'];
                        $lastEntry->addEntry($entry);
                    }

                    $entries[] = ['entry' => $entry, 'level' => $headerLevel];

                    if (count($entries) <= 1) {
                        $summary->addEntry($entry);
                    }
                }
            } catch (\Exception $e) {
            }
        }

        $page->setSummary($summary);
    }

    /////////////
    /// LINKS ///
    /////////////

    /**
     * Resolve relative path.
     *
     * @param string $initialPath
     * @param string $path
     *
     * @return string|false
     */
    public static function resolveRelativePath(string $initialPath, string $path)
    {
        if ((substr($path, 0, 1) == '/' && substr($path, 0, 2) !== '//') ||
            substr($path, 0, 2) == './' ||
            substr($path, 0, 3) == '../') {

            // Unification of directories separators
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
            $finalPath = dirname($initialPath);
            $finalPath = str_replace(DIRECTORY_SEPARATOR, '/', $finalPath);

            // Concatenation
            $finalPath = sprintf('%s/%s', rtrim($finalPath, '/'), ltrim($path, '/'));

            // Replacement of '//'
            $finalPath = preg_replace('#/{2,}#', '/', $finalPath);

            // Replacement of './'
            $finalPath = preg_replace('#/\./#', '/', $finalPath);

            // Replacement of '../'
            do {
                $finalPath = preg_replace('#/([^\\/?%*:|"<>\.]+)/../#', '/', $finalPath, -1, $nbReplacements);
            } while ($nbReplacements > 0);

            if (strpos($finalPath, './') === false) {
                return $finalPath;
            }
        }

        return false;
    }

    /**
     * Resolve absolute path.
     *
     * @param string $srcPath
     * @param string $dstPath
     *
     * @return string
     */
    public static function resolveAbsolutePath(string $srcPath, string $dstPath): string
    {
        $srcRelativePath = str_replace('\\', '/', $srcPath);
        $dstRelativePath = str_replace('\\', '/', $dstPath);
        $srcRelativePath = explode('/', $srcRelativePath);
        $dstRelativePath = explode('/', $dstRelativePath);

        $srcDepth = count($srcRelativePath) - 1;
        $dstDepth = count($dstRelativePath) - 1;
        $differentPath = false;

        for ($i = 0; $i < $srcDepth; $i++) {
            if ($differentPath === false) {
                if (!isset($dstRelativePath[$i]) || $srcRelativePath[$i] !== $dstRelativePath[$i]) {
                    $differentPath = $i;
                }
            }
        }

        $absolutePath = '';
        if ($differentPath !== false) {
            $absolutePath .= str_repeat('../', $srcDepth - $differentPath);
            $absolutePath .= implode('/', array_slice($dstRelativePath, $differentPath, $dstDepth));
        } else {
            $absolutePath .= './';
            $absolutePath .= implode('/', array_slice($dstRelativePath, $srcDepth, $dstDepth));
        }

        $absolutePath = preg_replace('#/{2,}#', '/', $absolutePath);

        return $absolutePath;
    }
}