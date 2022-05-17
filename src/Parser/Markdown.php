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
use Berlioz\Http\Message\Stream\MemoryStream;
use DateTimeImmutable;
use Exception;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\Flysystem\FileAttributes;

class Markdown implements ParserInterface
{
    private ConverterInterface $converter;

    /**
     * Markdown constructor.
     *
     * @param array $config
     * @param ConverterInterface|null $converter
     */
    public function __construct(array $config = [], ?ConverterInterface $converter = null)
    {
        if (null === $converter) {
            $environment = new Environment($config);
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new FrontMatterExtension());
            $converter = new MarkdownConverter($environment);
        }

        $this->converter = $converter;
    }

    /**
     * Get CommonMark converter.
     *
     * @return ConverterInterface
     */
    public function getConverter(): ConverterInterface
    {
        return $this->converter;
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
        return $extension == 'md';
    }

    /**
     * @inheritDoc
     */
    public function parse(string $src, FileAttributes $fileAttributes): ?Page
    {
        try {
            $result = $this->converter->convert($src);

            $stream = new MemoryStream();
            $stream->write($result->getContent());

            $page =
                new Page(
                    $stream->detach(),
                    $fileAttributes->path(),
                    $fileAttributes->mimeType(),
                    $fileAttributes->lastModified() ?
                        (new DateTimeImmutable())->setTimestamp($fileAttributes->lastModified()) : null
                );

            if ($result instanceof RenderedContentWithFrontMatter) {
                $page->setMetas((array)$result->getFrontMatter());
            }

            return $page;
        } catch (Exception $e) {
            throw new ParserException('An error occurred during parsing of content', 0, $e);
        }
    }
}