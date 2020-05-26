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

namespace Berlioz\DocParser\Doc\File;

/**
 * Interface ParsedFileInterface.
 *
 * @package Berlioz\DocParser\File
 */
interface ParsedFileInterface extends FileInterface
{
    /**
     * Get parsed content.
     *
     * @return string
     */
    public function getParsedContent(): string;

    /**
     * Set parsed content.
     *
     * @param string $parsedContent
     *
     * @return static
     */
    public function setParsedContent(string $parsedContent): ParsedFileInterface;
}