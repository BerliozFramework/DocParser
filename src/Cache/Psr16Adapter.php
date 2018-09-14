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

class Psr16Adapter implements CacheInterface
{
    const PREFIX = 'BERLIOZ_DOCPARSER';
    /** @var \Psr\SimpleCache\CacheInterface */
    private $cacheManager;
    /** @var int TTL */
    private $ttl;

    /**
     * Psr16Adapter constructor.
     *
     * @param \Psr\SimpleCache\CacheInterface $psr16CacheManager Cache manager
     * @param int|null                        $ttl               TTL for doc cache
     */
    public function __construct(\Psr\SimpleCache\CacheInterface $psr16CacheManager, int $ttl = null)
    {
        $this->cacheManager = $psr16CacheManager;
        $this->ttl = $ttl;
    }

    /**
     * Get documentation cache key.
     *
     * @param string      $uniqId
     * @param null|string ...$suffix
     *
     * @return string
     */
    private function getCacheKey(string $uniqId, ?string ...$suffix)
    {
        $key = sprintf('%s-%s', self::PREFIX, $uniqId);

        if (!empty($suffix)) {
            $key .= '-' . implode('-', $suffix);
        }

        return $key;
    }

    /**
     * Has documentation?
     *
     * @param string $uniqId Unique ID of documentation.
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function hasDocumentation(string $uniqId): bool
    {
        return $this->cacheManager->has($this->getCacheKey($uniqId));
    }

    /**
     * Get documentation.
     *
     * @param string $uniqId Unique ID of documentation.
     *
     * @return \Berlioz\DocParser\Documentation
     * @throws \Berlioz\DocParser\Exception\CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getDocumentation(string $uniqId): Documentation
    {
        if (!($documentation = $this->cacheManager->get($this->getCacheKey($uniqId))) instanceof Documentation) {
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function saveDocumentation(Documentation $documentation): CacheInterface
    {
        $cacheKeys = [];
        $this->clearDocumentation($documentation);

        $this->cacheManager->set($cacheKeys[] = $this->getCacheKey($documentation->getUniqid()), $documentation, $this->ttl);

        foreach ($documentation->getVersions() as $version) {
            $documentationVersion = $documentation->getVersion($version);
            $this->cacheManager->set($cacheKeys[] = $this->getCacheKey($documentation->getUniqid(), 'version', $version), $documentationVersion, $this->ttl);

            /** @var \Berlioz\DocParser\File\FileInterface $file */
            foreach ($documentationVersion->getFiles() as $file) {
                $fileContent = ['content' => $file->getContent()];
                if ($file instanceof ParsedFileInterface) {
                    $fileContent['parsedContent'] = $file->getParsedContent();
                }

                $this->cacheManager->set($cacheKeys[] = $this->getCacheKey($documentation->getUniqid(), 'file', $file->getHash()), $fileContent, $this->ttl);
            }
        }

        if (!$this->cacheManager->set($this->getCacheKey($documentation->getUniqid(), 'files'), $cacheKeys, $this->ttl)) {
            throw new CacheException(sprintf('Unable to save files index of documentation "%s" in cache', $documentation->getUniqid()));
        }

        return $this;
    }

    /**
     * Clear documentation cache.
     *
     * @param \Berlioz\DocParser\Documentation $documentation
     *
     * @return static
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function clearDocumentation(Documentation $documentation): CacheInterface
    {
        $cacheKeys = $this->cacheManager->get($cacheKey = $this->getCacheKey($documentation->getUniqid(), 'files'), []);
        $cacheKeys[] = $cacheKey;

        $this->cacheManager->deleteMultiple($cacheKeys);

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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getDocumentationVersion(Documentation $documentation, string $version): DocumentationVersion
    {
        $cacheKey = $this->getCacheKey($documentation->getUniqid(), 'version', $version);

        if (!($documentationVersion = $this->cacheManager->get($cacheKey)) instanceof DocumentationVersion) {
            throw new CacheException(sprintf('Unable to load version "%s" of documentation "%s" from cache',
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
     * @throws \Berlioz\DocParser\Exception\DocParserException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getFile(FileInterface $file): FileInterface
    {
        $cacheKey = $this->getCacheKey($file->getDocumentationVersion()->getDocumentation()->getUniqid(), 'file', $file->getHash());

        if (!(!empty($fileContent = $this->cacheManager->get($cacheKey)) && isset($fileContent['content']))) {
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