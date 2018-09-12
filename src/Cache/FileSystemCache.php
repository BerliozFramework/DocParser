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
use Berlioz\DocParser\Exception\CacheException;
use Berlioz\DocParser\File\FileInterface;
use Berlioz\DocParser\File\ParsedFileInterface;

class FileSystemCache implements CacheInterface
{
    /** @var string Cache directory */
    private $cacheDirectory;

    /**
     * FileSystemCache constructor.
     *
     * @param string|null $cacheDirectory
     */
    public function __construct(string $cacheDirectory = null)
    {
        if (is_null($cacheDirectory)) {
            $cacheDirectory = sys_get_temp_dir();
        }
        $this->cacheDirectory = rtrim($cacheDirectory, '\\/') . DIRECTORY_SEPARATOR . "DocParser";
    }

    /**
     * Get file name.
     *
     * @param string ...$name
     *
     * @return string
     */
    private function getFileName(string ...$name): string
    {
        $path = md5(implode('-', $name));
        $path = substr($path, 0, 2) . DIRECTORY_SEPARATOR . $path;
        $path = $this->cacheDirectory . DIRECTORY_SEPARATOR . $path;

        return $path;
    }

    /**
     * Save file.
     *
     * @param string $filename
     * @param mixed  $content
     *
     * @return bool
     */
    private function saveFile(string $filename, $content): bool
    {
        $dirname = dirname($filename);

        if (!is_dir($dirname) && @mkdir($dirname, 0777, true) === false) {
            return false;
        }

        return @file_put_contents($filename, serialize($content)) !== false;
    }

    /**
     * Has documentation?
     *
     * @param string $uniqId Unique ID of documentation.
     *
     * @return bool
     */
    public function hasDocumentation(string $uniqId): bool
    {
        return is_file($this->getFileName($uniqId));
    }

    /**
     * Get documentation.
     *
     * @param string $uniqId Unique ID of documentation.
     *
     * @return \Berlioz\DocParser\Documentation
     * @throws \Berlioz\DocParser\Exception\CacheException
     */
    public function getDocumentation(string $uniqId): Documentation
    {
        if (!is_file($filename = $this->getFileName($uniqId))) {
            throw new CacheException(sprintf('"%s" documentation does not exists in cache', $uniqId));
        }

        $documentation = unserialize(file_get_contents($filename));

        if (!$documentation instanceof Documentation) {
            throw new CacheException(sprintf('Unable to load "%s" documentation from cache', $uniqId));
        }

        return $documentation;
    }

    /**
     * Save documentation.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     *
     * @return static
     * @throws \Berlioz\DocParser\Exception\CacheException
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function saveDocumentation(Documentation $documentation): CacheInterface
    {
        $docFiles = [];
        $this->clearDocumentation($documentation);

        if (!$this->saveFile($docFiles[] = $this->getFileName($documentation->getUniqid()), $documentation)) {
            throw new CacheException(sprintf('Unable to write documentation "%s" in cache directory', $documentation->getUniqid()));
        }

        foreach ($documentation->getVersions() as $version) {
            $documentationVersion = $documentation->getVersion($version);
            if (!$this->saveFile($docFiles[] = $this->getFileName($documentation->getUniqid(), 'version', $version), $documentationVersion)) {
                throw new CacheException(sprintf('Unable to write version %s of documentation "%s" in cache directory', $version, $documentation->getUniqid()));
            }

            /** @var \Berlioz\DocParser\File\FileInterface $file */
            foreach ($documentationVersion->getFiles() as $file) {
                $fileContent = ['content' => $file->getContent()];
                if ($file instanceof ParsedFileInterface) {
                    $fileContent['parsedContent'] = $file->getParsedContent();
                }

                if (!$this->saveFile($docFiles[] = $this->getFileName($documentation->getUniqid(), 'file', $file->getHash()), $fileContent)) {
                    throw new CacheException(sprintf('Unable to write file "%s" of documentation "%s" in cache directory', $file->getFilename(), $documentation->getUniqid()));
                }
            }
        }

        if (!$this->saveFile($this->getFileName($documentation->getUniqid(), 'files'), $docFiles)) {
            throw new CacheException(sprintf('Unable to save files index of documentation "%s" in cache directory', $documentation->getUniqid()));
        }

        return $this;
    }

    /**
     * Clear documentation cache.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     *
     * @return static
     */
    public function clearDocumentation(Documentation $documentation): CacheInterface
    {
        if (is_file($filename = $this->getFileName($documentation->getUniqid(), 'files'))) {
            $files = unserialize(file_get_contents($filename));

            foreach ($files as $filename2) {
                @unlink($filename2);
            }

            @unlink($filename);
        }

        return $this;
    }

    /**
     * Get documentation version.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     * @param string                           $version
     *
     * @return \Berlioz\DocParser\Documentation\DocumentationVersion
     * @throws \Berlioz\DocParser\Exception\CacheException
     */
    public function getDocumentationVersion(Documentation $documentation, string $version): DocumentationVersion
    {
        if (!is_file($filename = $this->getFileName($documentation->getUniqid(), 'version', $version))) {
            throw new CacheException(sprintf('Version "%s" of documentation "%s" does not exists in cache',
                                             $version,
                                             $documentation->getUniqid()));
        }

        $documentationVersion = unserialize(file_get_contents($filename));

        if (!$documentationVersion instanceof DocumentationVersion) {
            throw new CacheException(sprintf('Unable to load version "%s" of documentation from cache',
                                             $version,
                                             $documentation->getUniqid()));
        }

        return $documentationVersion;
    }

    /**
     * Get file of documentation.
     *
     * @param \Berlioz\DocParser\File\FileInterface $file
     *
     * @return \Berlioz\DocParser\File\FileInterface
     * @throws \Berlioz\DocParser\Exception\CacheException
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getFile(FileInterface $file): FileInterface
    {
        if (!is_file($filename = $this->getFileName($file->getDocumentationVersion()->getDocumentation()->getUniqid(), 'file', $file->getHash()))) {
            throw new CacheException(sprintf('File "%s" of documentation "%s" does not exists in cache',
                                             $file->getFilename(),
                                             $file->getDocumentationVersion()->getDocumentation()->getUniqid()));
        }

        $fileContent = unserialize(file_get_contents($filename));

        if (!(!empty($fileContent) && isset($fileContent['content']))) {
            throw new CacheException(sprintf('Unable to load file "%s" of documentation "%s" from cache',
                                             $file->getFilename(),
                                             $file->getDocumentationVersion()->getDocumentation()->getUniqid()));
        }

        $file->setContent($fileContent['content']);

        if ($file instanceof ParsedFileInterface && isset($fileContent['parsedContent'])) {
            $file->setParsedContent($fileContent['parsedContent']);
        }

        return $file;
    }
}