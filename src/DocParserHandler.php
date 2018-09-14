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
use Berlioz\DocParser\Cache\CacheInterface;
use Berlioz\DocParser\Exception\DocParserException;
use Berlioz\DocParser\File\FileInterface;
use Berlioz\DocParser\Loader\LoaderInterface;

class DocParserHandler implements CacheAwareInterface
{
    use CacheAwareTrait;
    /** @var \Berlioz\DocParser\Loader\LoaderInterface Loader */
    private $loader;
    /** @var array Options */
    private $options;
    /** @var \Berlioz\DocParser\Documentation $documentation */
    private $documentation;

    /**
     * DocParserHandler constructor.
     *
     * @param \Berlioz\DocParser\Loader\LoaderInterface    $loader
     * @param \Berlioz\DocParser\Cache\CacheInterface|null $cache
     * @param array                                        $options
     */
    public function __construct(LoaderInterface $loader, ?CacheInterface $cache, array $options = [])
    {
        $this->loader = $loader;
        $this->setCache($cache);
        $this->options = $options;
    }

    /**
     * Get option.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getOption(string $name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * Generate documentation from loader.
     *
     * @throws \Berlioz\DocParser\Exception\DocParserException
     *
     * @return \Berlioz\DocParser\Documentation
     */
    protected function generate(): Documentation
    {
        $docGenerator = new Generator($this->options);

        return $docGenerator->handle($this->loader);
    }

    /**
     * Get documentation.
     *
     * @return \Berlioz\DocParser\Documentation|null
     * @throws \Berlioz\DocParser\Exception\CacheException
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getDocumentation(): ?Documentation
    {
        if (is_null($this->documentation)) {
            if (!$this->getCache()->hasDocumentation($this->loader->getUniqid())) {
                $this->documentation = $this->generate();
                $this->getCache()->saveDocumentation($this->documentation);

                return $this->documentation;
            }

            $this->documentation = $this->getCache()->getDocumentation($this->loader->getUniqid())->setCache($this->getCache());
        }

        return $this->documentation->setCache($this->getCache());
    }

    /**
     * Get versions links of documentation.
     *
     * @param \Berlioz\DocParser\File\FileInterface $file File
     *
     * @return array
     * @throws \Berlioz\DocParser\Exception\CacheException
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getVersionsLinks(FileInterface $file = null): array
    {
        $initialPath = sprintf('/%s/%s', $file->getDocumentationVersion()->getVersion(), ltrim($file->getUrlPath(), '/'));
        $versionsLinks = [];
        $versions = $this->getDocumentation()->getVersions();

        foreach ($versions as $version) {
            if (is_null($file)) {
                $versionsLinks[$version] = sprintf('/%s/', $version);
            } else {
                $documentationVersion = $this->getDocumentation()->getVersion($version);
                $versionFile =
                    $documentationVersion->getFiles()->findByPath($file->getUrlPath()) ?:
                        $documentationVersion->getFiles()->findByFilename($file->getFilename()) ?:
                            null;

                if (!is_null($versionFile)) {
                    $versionsLinks[$version] = sprintf('/%s/%s', $documentationVersion->getVersion(), ltrim($file->getUrlPath(), '/'));
                    $versionsLinks[$version] = Generator::resolveAbsolutePath($initialPath, $versionsLinks[$version]);
                }
            }
        }

        return $versionsLinks;
    }

    /**
     * Handle.
     *
     * @param string      $path
     * @param string|null $version
     *
     * @return \Berlioz\DocParser\File\FileInterface|null
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function handle(string $path, ?string $version = null): ?FileInterface
    {
        if (is_null($version)) {
            $versions = $this->getDocumentation()->getVersions();
            $version = reset($versions);
        }

        if (empty($version)) {
            return null;
        }

        $documentationVersion = $this->getDocumentation()->getVersion($version);

        // Find file
        $path = '/' . ltrim($path, '/');

        if (substr($path, -1) == '/') {
            foreach ((array) $this->getOption('fileIndex', ['index', 'readme', 'default']) as $indexFilename) {
                $indexPath = sprintf('%s/%s', rtrim($path, '/'), $indexFilename);

                if (!is_null($file = $documentationVersion->getFiles()->findByPath($indexPath))) {
                    return $file;
                }
            }

            return null;
        }

        return $documentationVersion->getFiles()->findByPath($path);
    }
}