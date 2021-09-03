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
use Berlioz\DocParser\Parser\RST\IndexDirective;
use Berlioz\DocParser\Parser\RST\IndexNode;
use Berlioz\Http\Message\Stream\MemoryStream;
use DateTimeImmutable;
use Exception;
use Gregwar\RST\Environment;
use Gregwar\RST\Kernel;
use Gregwar\RST\Nodes\Node;
use Gregwar\RST\Parser;
use League\Flysystem\FileAttributes;

class reStructuredText implements ParserInterface
{
    use TraitCastValue;

    private Parser $rstParser;

    /**
     * reStructuredText constructor.
     *
     * @param Environment|null $environment
     * @param Kernel|null $kernel
     */
    public function __construct(?Environment $environment = null, ?Kernel $kernel = null)
    {
        $this->rstParser = new Parser($environment, $kernel);
        $this->rstParser->registerDirective(new IndexDirective());
    }

    /**
     * Get RST parser.
     *
     * @return Parser
     */
    public function getRstParser(): Parser
    {
        return $this->rstParser;
    }

    ////////////////////////
    /// PARSER INTERFACE ///
    ////////////////////////

    /**
     * @inheritDoc
     */
    public function acceptMime(string $mime): bool
    {
        return in_array($mime, ['text/rst', 'text/x-rst']);
    }

    /**
     * @inheritDoc
     */
    public function acceptExtension(string $extension): bool
    {
        return $extension == 'rst';
    }

    /**
     * @inheritDoc
     */
    public function parse(string $src, FileAttributes $fileAttributes): Page
    {
        try {
            $rstFile = $this->rstParser->parse($src);

            $stream = new MemoryStream();
            $stream->write((string)$rstFile->render());

            $page =
                new Page(
                    $stream->detach(),
                    $fileAttributes->path(),
                    $fileAttributes->mimeType(),
                    $fileAttributes->lastModified() ?
                        (new DateTimeImmutable())->setTimestamp($fileAttributes->lastModified()) : null
                );
            $page->setTitle($rstFile->getTitle());

            // Get metas from parser
            $metas = [];
            foreach ($rstFile->getNodes(fn(Node $node) => $node instanceof IndexNode) as $indexNode) {
                $metas = array_replace($metas, $indexNode->getValue());
            }
            array_walk_recursive($metas, fn(&$value) => $value = $this->castValue($value));
            $page->setMetas($metas);

            return $page;
        } catch (Exception $e) {
            throw new ParserException('An error occurred during parsing of content', 0, $e);
        }
    }
}