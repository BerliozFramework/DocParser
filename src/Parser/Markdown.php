<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\DocParser\Parser;

use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Exception\ParserException;
use Berlioz\DocParser\Parser\CommonMark\IndexExtension;
use Berlioz\Http\Message\Stream\MemoryStream;
use DateTimeImmutable;
use Exception;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\Flysystem\FileAttributes;

/**
 * Class Markdown.
 *
 * @package Berlioz\DocParser\Parser
 */
class Markdown implements ParserInterface
{
    private MarkdownConverter $markdownConverter;
    private IndexExtension $indexExtension;

    /**
     * Markdown constructor.
     *
     * @param array $config
     * @param ConfigurableEnvironmentInterface|null $environment
     */
    public function __construct(array $config = [], ?ConfigurableEnvironmentInterface $environment = null)
    {
        if (null === $environment) {
            $environment = Environment::createCommonMarkEnvironment();
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
        }

        // Add default index extension
        $environment->mergeConfig($config);
        $environment->addExtension($this->indexExtension = new IndexExtension());

        $this->markdownConverter = new MarkdownConverter($environment);
    }

    /**
     * Get CommonMark converter.
     *
     * @return MarkdownConverter
     */
    public function getMarkdownConverter(): MarkdownConverter
    {
        return $this->markdownConverter;
    }

    ////////////////////////
    /// PARSER INTERFACE ///
    ////////////////////////

    /**
     * @inheritDoc
     */
    public function acceptMime(string $mime): bool
    {
        return in_array($mime, ['text/markdown', 'text/x-markdown']);
    }

    /**
     * @inheritDoc
     */
    public function acceptExtension(string $extension): bool
    {
        return in_array($extension, ['md']);
    }

    /**
     * @inheritDoc
     */
    public function parse(string $src, FileAttributes $fileAttributes): Page
    {
        try {
            $stream = new MemoryStream();
            $stream->write($this->markdownConverter->convertToHtml($src));

            $page =
                new Page(
                    $stream->detach(),
                    $fileAttributes->path(),
                    $fileAttributes->mimeType(),
                    $fileAttributes->lastModified() ?
                        (new DateTimeImmutable())->setTimestamp($fileAttributes->lastModified()) : null
                );

            $page->setMetas($this->indexExtension->getIndex());

            return $page;
        } catch (Exception $e) {
            throw new ParserException('An error occurred during parsing of content', 0, $e);
        }
    }
}