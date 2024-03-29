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

namespace Berlioz\DocParser;

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\FileInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Throwable;

class DocCacheGenerator
{
    /**
     * DocCacheGenerator constructor.
     *
     * @param FilesystemOperator $filesystem
     * @param string $location
     * @param array $config
     */
    public function __construct(
        protected FilesystemOperator $filesystem,
        protected string $location = '/',
        protected array $config = [],
    ) {
    }

    /**
     * Get filename from hash.
     *
     * @param string $hash
     *
     * @return string
     */
    private function getHashFilename(string $hash): string
    {
        return sprintf('%s/%s', substr($hash, 0, 2), $hash);
    }

    /**
     * Get doc cache name.
     *
     * @param string $version
     *
     * @return string
     */
    public function getDocCacheName(string $version): string
    {
        return rtrim($this->location, '/') . '/' . ltrim($this->getHashFilename(md5($version)), '/');
    }

    /**
     * Get file cache name.
     *
     * @param FileInterface $file
     *
     * @return string
     */
    public function getFileCacheName(FileInterface $file): string
    {
        return rtrim($this->location, '/') . '/' . ltrim($this->getHashFilename($file->getHash()), '/');
    }

    /////////////////////
    /// DOCUMENTATION ///
    /////////////////////

    /**
     * Get version.
     *
     * @param string $version
     *
     * @return Documentation|null
     */
    public function get(string $version): ?Documentation
    {
        try {
            if (!$this->filesystem->fileExists($this->getDocCacheName($version))) {
                return null;
            }

            $documentation = $this->filesystem->read($this->getDocCacheName($version));
            $documentation = unserialize($documentation);

            if (!$documentation instanceof Documentation) {
                return null;
            }

            /** @var FileInterface $file */
            foreach ($documentation->getFiles() as $file) {
                $this->readFile($file);
            }

            return $documentation;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Save documentation.
     *
     * @param Documentation $documentation
     *
     * @throws FilesystemException
     */
    public function save(Documentation $documentation): void
    {
        /** @var FileInterface $file */
        foreach ($documentation->getFiles() as $file) {
            $this->saveFile($file);
        }

        $this->filesystem->write(
            $this->getDocCacheName($documentation->getVersion()),
            serialize($documentation),
            $this->config,
        );
    }

    ////////////
    /// FILE ///
    ////////////

    /**
     * Read file.
     *
     * @param FileInterface $file
     *
     * @throws FilesystemException
     */
    public function readFile(FileInterface $file): void
    {
        $file->setStream($this->filesystem->readStream($this->getFileCacheName($file)));
    }

    /**
     * Save file.
     *
     * @param FileInterface $file
     *
     * @throws FilesystemException
     */
    public function saveFile(FileInterface $file): void
    {
        $this->filesystem->writeStream(
            $this->getFileCacheName($file),
            $file->getStream(),
            $this->config,
        );
    }
}