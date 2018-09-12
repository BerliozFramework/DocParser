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

namespace Berlioz\DocParser\Documentation;

use Berlioz\DocParser\Documentation;
use Berlioz\DocParser\File\FileSet;
use Berlioz\DocParser\Summary;

class DocumentationVersion implements \Serializable
{
    /** @var \Berlioz\DocParser\Documentation Documentation */
    private $documentation;
    /** @var string Version */
    private $version;
    /** @var \Berlioz\DocParser\Summary Summary */
    private $summary;
    /** @var \Berlioz\DocParser\File\FileSet Files */
    private $files;

    /**
     * DocumentationVersion constructor.
     *
     * @param string $version
     */
    public function __construct(string $version)
    {
        $this->version = $version;
    }

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['version' => $this->version,
                'summary' => $this->summary,
                'files'   => $this->files];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['version' => $this->version,
                          'summary' => $this->summary,
                          'files'   => $this->files]);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->version = $unserialized['version'];
        $this->summary = $unserialized['summary'];
        $this->files = $unserialized['files'];
        $this->files->setDocumentationVersion($this);
    }

    /**
     * Get documentation.
     *
     * @return \Berlioz\DocParser\Documentation
     */
    public function getDocumentation(): Documentation
    {
        return $this->documentation;
    }

    /**
     * Set documentation.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     *
     * @return static
     */
    public function setDocumentation(Documentation $documentation): DocumentationVersion
    {
        $this->documentation = $documentation;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return static
     */
    public function setVersion(string $version): DocumentationVersion
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get summary.
     *
     * @return \Berlioz\DocParser\Summary
     */
    public function getSummary(): Summary
    {
        if (is_null($this->summary)) {
            $this->summary = new Summary();
        }

        return $this->summary;
    }

    /**
     * Set summary.
     *
     * @param \Berlioz\DocParser\Summary $summary
     *
     * @return static
     */
    public function setSummary(Summary $summary): DocumentationVersion
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get files.
     *
     * @return \Berlioz\DocParser\File\FileSet
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getFiles(): FileSet
    {
        if (is_null($this->files)) {
            $this->files = new FileSet();
            $this->files->setDocumentationVersion($this);
        }

        return $this->files;
    }
}