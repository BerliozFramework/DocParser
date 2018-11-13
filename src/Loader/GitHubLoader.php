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
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\Uri;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

class GitHubLoader extends AbstractLoader implements LoaderInterface
{
    const API_URL = 'https://api.github.com/graphql';
    /** @var \Http\Client\HttpClient Http client */
    private $httpClient;
    /** @var array Versions */
    private $versions;
    /** @var array Files */
    private $files;

    /**
     * GitHubLoader constructor.
     *
     * @param array                        $options    Options
     * @param \Http\Client\HttpClient|null $httpClient HTTP client
     *
     * @option string token      GitHub token
     * @option string owner      Owner name
     * @option string repository Repository name
     * @option string path       First repository directory
     * @option string branch     Default branch if not versioned
     * @option string branches   Branches to get for versioned documentation (empty = all)
     */
    public function __construct(array $options, HttpClient $httpClient)
    {
        parent::__construct($options);

        $this->options['path'] = '/' . trim($this->getOption('path', '/'), '/');

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

            // Do HTTP request
            $response = $this->doRequest(
                'POST',
                <<<EOD
{
  repository(name: "{$this->getOption('repository')}", owner: "{$this->getOption('owner')}") {
    refs(first: 100, refPrefix:"refs/heads/") {
      edges {
        node {
          name
        }
      }
    }
  }
}
EOD
            );

            if (($jsonResponse = json_decode((string) $response->getBody(), true)) !== false) {
                if (!empty($branches = $jsonResponse['data']['repository']['refs']['edges'])) {
                    foreach ($branches as $branch) {
                        $this->versions[] = $branch['node']['name'];
                    }
                }
            }

            // Filter branches
            if (is_array($this->getOption('branches'))) {
                $this->versions =
                    array_filter(
                        $this->versions,
                        function ($value) {
                            return in_array($value, $this->getOption('branches'));
                        });
            }
        }

        return $this->versions;
    }

    /**
     * @inheritdoc
     */
    public function getFiles(string $version): array
    {
        $this->loadFromGithub($version);

        return $this->files[$version];
    }

    /////////////////////
    /// UTILS METHODS ///
    /////////////////////

    /**
     * Get http client.
     *
     * @return \Http\Client\HttpClient
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function getHttpClient(): HttpClient
    {
        if (is_null($this->httpClient)) {
            throw new LoaderException(sprintf('Missing http client for loader "%s"', static::class));
        }

        return $this->httpClient;
    }

    /**
     * Load files from GitHub.
     *
     * @param string $version
     *
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function loadFromGithub(string $version)
    {
        if (!isset($this->files[$version])) {
            $this->files[$version] = $this->graphqlRequest($version, [$this->getOption('path', '/')]);
        }
    }

    /**
     * Do request on GitHub API.
     *
     * @param string $method      Http method
     * @param string $requestBody Request body
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function doRequest(string $method, string $requestBody): ResponseInterface
    {
        try {
            try {
                // Request
                $requestStream = new Stream();
                $requestStream->write(json_encode(['query' => $requestBody]));
                $request = new Request($method, Uri::createFromString(static::API_URL));
                $request = $request->withHeader('Authorization', sprintf('bearer %s', $this->getOption('token')))
                                   ->withHeader('Content-Type', 'application/json')
                                   ->withBody($requestStream);


                $response = $this->getHttpClient()->sendRequest($request);
            } catch (\Http\Client\Exception $e) {
                throw new LoaderException('Http client error', 0, $e);
            }

            return $response;
        } catch (\Exception $e) {
            throw new LoaderException('Unable to dialog with GitHub API', 0, $e);
        }
    }

    /**
     * Do GraphQL request on GitHub Api to get directories content.
     *
     * @param string $version     Version
     * @param array  $directories Directories to get content
     *
     * @return \Berlioz\DocParser\File\FileInterface[]
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function graphqlRequest(string $version, array $directories): array
    {
        $files = [];
        $graphqlRequest = '';
        $finfo = new \finfo(FILEINFO_MIME);

        // Create GraphQL body request
        foreach ($directories as &$directory) {
            $directory = trim($directory, '/') . '/';
            $directory = ltrim($directory, '/');
            $directoryHash = sprintf('dir_%s', md5($directory));

            $graphqlRequest .= <<<EOD
    {$directoryHash}: object(expression: "{$version}:{$directory}") {
      ... on Tree {
        entries {
          oid
          name
          type
          content: object {
            ...on Blob {
              text
            }
            repository {
              updatedAt
            }
          }
        }
      }
    }
EOD;
        }
        unset($directory);

        // Do HTTP request
        $response = $this->doRequest(
            'POST',
            <<<EOD
{
  repository(name: "{$this->getOption('repository')}", owner: "{$this->getOption('owner')}") {
    {$graphqlRequest}
  }
}
EOD
        );

        if (($jsonResponse = json_decode((string) $response->getBody(), true)) !== false) {
            $subDirectories = [];

            foreach ($directories as $directory) {
                $directoryHash = sprintf('dir_%s', md5($directory));

                if (!empty($directoryContent = $jsonResponse['data']['repository'][$directoryHash]['entries'])) {
                    foreach ($directoryContent as $file) {
                        $fullFilename = '/' . ltrim($directory, '/') . $file['name'];
                        $fullFilename = substr($fullFilename, mb_strlen(rtrim('/' . $this->getOption('path', '/'), '/')));

                        if ($file['type'] == 'tree') {
                            $subDirectories[] = $fullFilename;
                        } else {
                            if ($this->testFilter($fullFilename)) {
                                if (!empty($file['content']['text'])) {
                                    $content = $file['content']['text'];
                                } else {
                                    $content = file_get_contents(sprintf('https://github.com/%s/%s/raw/%s/%s',
                                                                         $this->getOption('owner'),
                                                                         $this->getOption('repository'),
                                                                         $version,
                                                                         ltrim($fullFilename, '/')));
                                }

                                if (!is_null($content) && $content !== false) {
                                    // File
                                    $rawFile = new RawFile();
                                    $rawFile->setHash(sha1($content))
                                            ->setFilename($fullFilename)
                                            ->setContent($content)
                                            ->setMime($finfo->buffer($content))
                                            ->setDatetime(new \DateTime($file['content']['repository']['updatedAt']));

                                    $files[] = $rawFile;
                                }
                            }
                        }
                    }
                }
            }

            // If subdirectories detected, recursive call
            if (!empty($subDirectories)) {
                $files = array_merge($files, $this->graphqlRequest($version, $subDirectories));
            }
        } else {
            throw new LoaderException('Unable to get content of files');
        }

        return $files;
    }
}