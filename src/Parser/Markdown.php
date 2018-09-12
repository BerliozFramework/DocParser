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

namespace Berlioz\DocParser\Parser;

use Berlioz\DocParser\Exception\ParserException;
use Berlioz\DocParser\File\FileInterface;
use Berlioz\DocParser\File\Page;

class Markdown implements ParserInterface
{
    /** @var \ParsedownExtra Parser */
    private $parsedownExtra;

    /**
     * Get ParseDownExtra library.
     *
     * @return \ParsedownExtra
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    protected function getParsedownExtra(): \ParsedownExtra
    {
        if (is_null($this->parsedownExtra)) {
            try {
                $this->parsedownExtra = new \ParsedownExtra();
            } catch (\Throwable $e) {
                throw new ParserException('Error during initilization of Markdown parser', 0, $e);
            }
        }

        return $this->parsedownExtra;
    }

    ////////////////////////
    /// PARSER INTERFACE ///
    ////////////////////////

    /**
     * @inheritdoc
     */
    public function acceptMime(string $mime): bool
    {
        return in_array($mime, ['text/markdown', 'text/x-markdown']);
    }

    /**
     * @inheritdoc
     */
    public function acceptExtension(string $extension): bool
    {
        return in_array($extension, ['md']);
    }

    /**
     * @inheritdoc
     */
    public function parse(FileInterface $srcFile): FileInterface
    {
        try {
            $page = new Page($srcFile);
            $page->setParsedContent((string) $this->getParsedownExtra()->parse($page->getContent()))
                 ->setMime('text/html');

            return $page;
        } catch (\Exception $e) {
            throw new ParserException('An error occurred during parsing of content', 0, $e);
        }
    }
}