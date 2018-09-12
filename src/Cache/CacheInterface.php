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

namespace Berlioz\DocParser\Cache;

use Berlioz\DocParser\Documentation;
use Berlioz\DocParser\Documentation\DocumentationVersion;
use Berlioz\DocParser\File\FileInterface;

interface CacheInterface
{
    /////////////////////
    /// DOCUMENTATION ///
    /////////////////////

    /**
     * Has documentation?
     *
     * @param string $uniqId Unique ID of documentation.
     *
     * @return bool
     */
    public function hasDocumentation(string $uniqId): bool;

    /**
     * Get documentation.
     *
     * @param string $uniqId Unique ID of documentation.
     *
     * @return \Berlioz\DocParser\Documentation
     * @throws \Berlioz\DocParser\Exception\CacheException
     */
    public function getDocumentation(string $uniqId): Documentation;

    /**
     * Save documentation.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     *
     * @return static
     * @throws \Berlioz\DocParser\Exception\CacheException
     */
    public function saveDocumentation(Documentation $documentation): CacheInterface;

    /**
     * Clear documentation cache.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     *
     * @return static
     */
    public function clearDocumentation(Documentation $documentation): CacheInterface;

    /////////////////////////////
    /// DOCUMENTATION VERSION ///
    /////////////////////////////

    /**
     * Get documentation version.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     * @param string                           $version
     *
     * @return \Berlioz\DocParser\Documentation\DocumentationVersion
     * @throws \Berlioz\DocParser\Exception\CacheException
     */
    public function getDocumentationVersion(Documentation $documentation, string $version): DocumentationVersion;

    ////////////
    /// FILE ///
    ////////////

    /**
     * Get file of documentation.
     *
     * @param \Berlioz\DocParser\File\FileInterface $file
     *
     * @return \Berlioz\DocParser\File\FileInterface
     * @throws \Berlioz\DocParser\Exception\CacheException
     */
    public function getFile(FileInterface $file): FileInterface;
}