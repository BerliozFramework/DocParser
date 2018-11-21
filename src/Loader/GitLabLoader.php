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

use Berlioz\DocParser\Exception\LoaderException;
use Berlioz\DocParser\File\RawFile;
use Berlioz\Http\Message\Request;
use Berlioz\Http\Message\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class GitLabLoader extends AbstractLoader implements LoaderInterface
{
    const API_URL = 'https://api.github.com/graphql';
    /** @var \Psr\Http\Client\ClientInterface Http client */
    private $httpClient;
    /** @var array Versions */
    private $versions;
    /** @var \Berlioz\DocParser\File\FileInterface[][] Files */
    private $files = [];

    /**
     * GitLabLoader constructor.
     *
     * @param array                        $options    Options
     * @param \Psr\Http\Client\ClientInterface|null $httpClient HTTP client
     *
     * @option string api        GitLab API link
     * @option string token      GitLab token
     * @option string project    Project name
     * @option string path       First repository directory
     * @option string branch     Default branch if not versioned
     * @option string branches   Branches to get for versioned documentation (empty = all)
     */
    public function __construct(array $options, ClientInterface $httpClient)
    {
        parent::__construct($options);

        $this->options['path'] = trim($this->getOption('path', '/') . '/', '/ ');

        // Http providers
        $this->httpClient = $httpClient;
    }

    ////////////////////////
    /// LOADER INTERFACE ///
    ////////////////////////

    /**
     * Get unique id uses for cache.
     *
     * @return string
     */
    public function getUniqId(): string
    {
        return sha1(serialize($this->options));
    }

    /**
     * @inheritdoc
     */
    public function getVersions(): array
    {
        if (is_null($this->versions)) {
            $this->versions = [];

            $response = $this->doRequest('GET',
                                         sprintf('%s/api/v4/projects/%s/repository/branches',
                                                 $this->getOption('api'),
                                                 urlencode($this->getOption('project'))));

            if (($jsonResponse = json_decode((string) $response->getBody(), true)) !== false) {
                foreach ($jsonResponse as $branch) {
                    $this->versions[$branch['name']] = new \DateTime($branch['commit']['committed_date']);
                }
            } else {
                throw new LoaderException('Unable to get versions');
            }

            // Filter branches
            if (is_array($this->getOption('branches'))) {
                $this->versions =
                    array_filter(
                        $this->versions,
                        function ($key) {
                            return in_array($key, $this->getOption('branches'));
                        },
                        ARRAY_FILTER_USE_KEY);
            }
        }

        return array_keys($this->versions);
    }

    /**
     * @inheritdoc
     */
    public function getFiles(string $version): array
    {
        $this->loadFromGitLab($version);

        return $this->files[$version];
    }

    /////////////////////
    /// UTILS METHODS ///
    /////////////////////

    /**
     * Get http client.
     *
     * @return \Psr\Http\Client\ClientInterface
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function getHttpClient(): ClientInterface
    {
        if (is_null($this->httpClient)) {
            throw new LoaderException(sprintf('Missing http client for loader "%s"', static::class));
        }

        return $this->httpClient;
    }

    /**
     * Do request on GitLab API.
     *
     * @param string $method Http method
     * @param string $uri    URI
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function doRequest(string $method, string $uri): ResponseInterface
    {
        try {
            // Request
            $request = new Request($method, Uri::createFromString($uri));
            $request = $request->withHeader('Private-Token', $this->getOption('token'))
                               ->withHeader('Content-Type', 'application/json');
            $response = $this->getHttpClient()->sendRequest($request);

            return $response;
        } catch (ClientExceptionInterface $e) {
            throw new LoaderException('Unable to dialog with GitLab API', 0, $e);
        } catch (\Exception $e) {
            throw new LoaderException('Impossible to dialog with GitLab API', 0, $e);
        }
    }

    /**
     * Load files from GitLab.
     *
     * @param string $version
     *
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function loadFromGitLab(string $version)
    {
        if (!isset($this->files[$version])) {
            $this->files[$version] = [];
            $finfo = new \finfo(FILEINFO_MIME);

            $page = 0;
            $nbPage = 1;
            while ($page < $nbPage) {
                $page++;
                $response = $this->doRequest('GET',
                                             sprintf('%s/api/v4/projects/%s/repository/tree?recursive=true&ref=%s&path=%s&per_page=100&page=%d',
                                                     $this->getOption('api'),
                                                     urlencode($this->getOption('project')),
                                                     $version,
                                                     $this->getOption('path'),
                                                     $page));

                if (($jsonResponse = json_decode((string) $response->getBody(), true)) !== false) {
                    foreach ($jsonResponse as $entry) {
                        $fullFilename = '/' . trim($entry['path'], '/');

                        if ($entry['type'] == 'blob') {
                            if ($this->testFilter($fullFilename)) {
                                $content = $this->getFileFromGitLab($entry['id']);

                                // File
                                $rawFile = new RawFile();
                                $rawFile->setHash(sha1($content))
                                        ->setFilename($fullFilename)
                                        ->setContent($content)
                                        ->setMime($finfo->buffer($content))
                                        ->setDatetime($this->versions[$version]);

                                $this->files[$version][$fullFilename] = $rawFile;
                            }
                        }
                    }
                }

                // Get nb pages
                if (!empty($nextPageHeader = $response->getHeader('X-Total-Pages'))) {
                    $nbPage = intval($response->getHeader('X-Total-Pages')[0]);
                }
            }
        }
    }

    /**
     * Get file from GitLab.
     *
     * @param string $id
     *
     * @return bool|string
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function getFileFromGitLab(string $id)
    {
        $response = $this->doRequest('GET',
                                     sprintf('%s/api/v4/projects/%s/repository/blobs/%s',
                                             $this->getOption('api'),
                                             urlencode($this->getOption('project')),
                                             $id));

        if (($jsonResponse = json_decode((string) $response->getBody(), true)) !== false) {
            if ($jsonResponse['encoding'] == 'base64') {
                return base64_decode($jsonResponse['content']);
            } else {
                throw new LoaderException(sprintf('Unable to get file id "%s", bad encoding', $id));
            }
        } else {
            throw new LoaderException(sprintf('Unable to get file id "%s"', $id));
        }
    }
}