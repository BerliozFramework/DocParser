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

namespace Berlioz\DocParser\File;

use Berlioz\DocParser\Documentation\DocumentationVersion;
use Berlioz\DocParser\Exception\DocParserException;

class FileSet implements \Serializable, \IteratorAggregate, \Countable
{
    /** @var \Berlioz\DocParser\Documentation\DocumentationVersion Documentation version */
    protected $documentationVersion;
    /** @var \Berlioz\DocParser\File\FileInterface[] Files */
    private $files = [];

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['files' => $this->files];
    }

    /**
     * Get documentation version.
     *
     * @return \Berlioz\DocParser\Documentation\DocumentationVersion
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getDocumentationVersion(): DocumentationVersion
    {
        if (is_null($this->documentationVersion)) {
            throw new DocParserException('Missing documentation version object reference');
        }

        return $this->documentationVersion;
    }

    /**
     * Set documentation version.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentationVersion
     *
     * @return static
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function setDocumentationVersion(DocumentationVersion $documentationVersion)
    {
        $this->documentationVersion = $documentationVersion;

        foreach ($this->files as $file) {
            $file->setDocumentationVersion($this->getDocumentationVersion());
        }

        return $this;
    }

    //////////////////////////////
    /// SERIALIZABLE INTERFACE ///
    //////////////////////////////

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['files' => $this->files]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);
        $this->files = $unserialized['files'];
    }

    ///////////////////////////////////
    /// ITERATORAGGREGATE INTERFACE ///
    ///////////////////////////////////

    /**
     * Implementation of \IteratorAggregate interface.
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->files);
    }

    ///////////////////////////
    /// COUNTABLE INTERFACE ///
    ///////////////////////////

    /**
     * Count files.
     *
     * @return int
     */
    public function count()
    {
        return count($this->files);
    }

    /////////////
    /// FILES ///
    /////////////

    /**
     * Add file.
     *
     * @param \Berlioz\DocParser\File\FileInterface $file
     *
     * @return static
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function addFile(FileInterface $file): FileSet
    {
        $this->files[] = $file;
        $file->setDocumentationVersion($this->getDocumentationVersion());

        return $this;
    }

    /**
     * Get files.
     *
     * @param string|null $classFilter Class filter
     *
     * @return array
     */
    public function getFiles(?string $classFilter = null): array
    {
        if (is_null($classFilter)) {
            return $this->files;
        }

        return array_filter(
            $this->files,
            function ($value) use ($classFilter) {
                return is_a($value, $classFilter);
            }
        );
    }

    /**
     * Find by filename.
     *
     * @param string $filename
     *
     * @return \Berlioz\DocParser\File\FileInterface|null
     */
    public function findByFilename(string $filename): ?FileInterface
    {
        /** @var \Berlioz\DocParser\File\FileInterface $file */
        foreach ($this as $file) {
            if ($file->getFilename() == $filename) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Find file by path.
     *
     * @param string $url_path
     *
     * @return \Berlioz\DocParser\File\FileInterface|null
     */
    public function findByPath(string $url_path): ?FileInterface
    {
        /** @var \Berlioz\DocParser\File\FileInterface $file */
        foreach ($this as $file) {
            if ($file->getUrlPath() == $url_path) {
                return $file;
            }
        }

        return null;
    }
}