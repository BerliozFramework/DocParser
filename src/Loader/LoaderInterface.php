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

namespace Berlioz\DocParser\Loader;

interface LoaderInterface
{
    /**
     * Get unique id of documentation.
     *
     * Returned value MUST NOT be a random value.
     *
     * @return string
     */
    public function getUniqid(): string;

    /**
     * Get versions of documentation.
     *
     * @return string[]
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    public function getVersions(): array;

    /**
     * Get files.
     *
     * @param string $version Version
     *
     * @return \Berlioz\DocParser\File\FileInterface[]
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    public function getFiles(string $version): array;
}