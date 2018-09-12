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

namespace Berlioz\DocParser;

use Berlioz\DocParser\Cache\CacheAwareInterface;
use Berlioz\DocParser\Cache\CacheAwareTrait;
use Berlioz\DocParser\Documentation\DocumentationVersion;
use Berlioz\DocParser\Exception\DocParserException;

class Documentation implements CacheAwareInterface, \Serializable
{
    use CacheAwareTrait;
    private $uniqId;
    /** @var string[] Versions */
    private $versions = [];
    /** @var \Berlioz\DocParser\Documentation\DocumentationVersion[] Documentation versions */
    private $documentationVersions = [];

    /**
     * Documentation constructor.
     *
     * @param string $uniqId
     */
    public function __construct(string $uniqId)
    {
        $this->uniqId = $uniqId;
    }

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['uniqId'                => $this->uniqId,
                'versions'              => $this->versions,
                'documentationVersions' => $this->documentationVersions];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['uniqId'   => $this->uniqId,
                          'versions' => $this->versions]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->uniqId = $unserialized['uniqId'];
        $this->versions = $unserialized['versions'];
    }

    /**
     * Get unique id of documentation.
     *
     * @return string
     */
    public function getUniqid(): string
    {
        return $this->uniqId;
    }

    /**
     * Get versions.
     *
     * @return string[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * Has version?
     *
     * @param string $version
     *
     * @return bool
     */
    public function hasVersion(string $version): bool
    {
        return in_array($version, $this->versions);
    }

    /**
     * Get documentation version.
     *
     * @param string $version
     *
     * @return \Berlioz\DocParser\Documentation\DocumentationVersion
     * @throws \Berlioz\DocParser\Exception\DocParserException If documentation does not exists
     */
    public function getVersion(string $version): DocumentationVersion
    {
        if (!isset($this->documentationVersions[$version])) {
            if (!$this->hasCache()) {
                throw new DocParserException('Missing cache in Documentation object');
            }

            if (!in_array($version, $this->versions)) {
                throw new DocParserException(sprintf('Version "%s" of documentation not found', $version));
            }

            $this->documentationVersions[$version] = $this->getCache()->getDocumentationVersion($this, $version);
            $this->documentationVersions[$version]->setDocumentation($this);
        }

        return $this->documentationVersions[$version];
    }

    /**
     * Add version.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentationVersion
     *
     * @return static
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function addVersion(DocumentationVersion $documentationVersion): Documentation
    {
        if (in_array($documentationVersion->getVersion(), $this->versions)) {
            throw new DocParserException(sprintf('Version "%s" already exists in documentation', $documentationVersion->getVersion()));
        }
        $this->versions[] = $documentationVersion->getVersion();
        $this->documentationVersions[$documentationVersion->getVersion()] = $documentationVersion;

        return $this;
    }
}