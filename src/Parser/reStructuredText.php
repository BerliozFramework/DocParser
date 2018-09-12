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

class reStructuredText implements ParserInterface
{
    /** @var \Gregwar\RST\Parser */
    private $rstParser;

    /**
     * Get RST Parser.
     *
     * @return \Gregwar\RST\Parser
     */
    public function getRstParser(): \Gregwar\RST\Parser
    {
        if (is_null($this->rstParser)) {
            $this->rstParser = new \Gregwar\RST\Parser;
        }

        return $this->rstParser;
    }

    /**
     * Set RST Parser.
     *
     * @param \Gregwar\RST\Parser $rstParser
     *
     * @return \Berlioz\DocParser\Parser\reStructuredText
     */
    public function setRstParser(\Gregwar\RST\Parser $rstParser): reStructuredText
    {
        $this->rstParser = $rstParser;

        return $this;
    }

    ////////////////////////
    /// PARSER INTERFACE ///
    ////////////////////////

    /**
     * @inheritdoc
     */
    public function acceptMime(string $mime): bool
    {
        return in_array($mime, ['text/rst', 'text/x-rst']);
    }

    /**
     * @inheritdoc
     */
    public function acceptExtension(string $extension): bool
    {
        return in_array($extension, ['rst']);
    }

    /**
     * @inheritdoc
     */
    public function parse(FileInterface $srcFile): FileInterface
    {
        try {
            $rstFile = $this->getRstParser()->parse($srcFile->getContent());

            $page = new Page($srcFile);
            $page->setTitle($rstFile->getTitle())
                 ->setParsedContent((string) $rstFile->render())
                 ->setMime('text/html');

            return $page;
        } catch (\Exception $e) {
            throw new ParserException('An error occurred during parsing of content', 0, $e);
        }
    }
}