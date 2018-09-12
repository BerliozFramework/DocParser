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

use Berlioz\DocParser\File\FileInterface;

interface ParserInterface
{
    /**
     * Accept mime?
     *
     * @param string $mime
     *
     * @return bool
     */
    public function acceptMime(string $mime): bool;

    /**
     * Accept extension?
     *
     * @param string $extension
     *
     * @return bool
     */
    public function acceptExtension(string $extension): bool;

    /**
     * Parse content.
     *
     * @param \Berlioz\DocParser\File\FileInterface $srcFile Source file
     *
     * @return \Berlioz\DocParser\File\FileInterface
     * @throws \Berlioz\DocParser\Exception\ParserException if an error occurred during parsing
     */
    public function parse(FileInterface $srcFile): FileInterface;
}