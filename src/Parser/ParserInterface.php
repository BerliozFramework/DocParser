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

use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Exception\ParserException;
use League\Flysystem\FileAttributes;

/**
 * Interface ParserInterface.
 *
 * @package Berlioz\DocParser\Parser
 */
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
     * Accept file extension?
     *
     * @param string $extension
     *
     * @return bool
     */
    public function acceptExtension(string $extension): bool;

    /**
     * Parse content.
     *
     * @param string $src Source
     * @param FileAttributes $fileAttributes
     *
     * @return FileInterface
     * @throws ParserException
     */
    public function parse(string $src, FileAttributes $fileAttributes): FileInterface;
}