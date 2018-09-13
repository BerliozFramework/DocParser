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
use Berlioz\DocParser\File\FileInterface;
use Berlioz\DocParser\File\RawFile;

class FileSystemLoader extends AbstractLoader implements LoaderInterface
{
    /** @var string Base path */
    private $basePath;
    /** @var array Versions */
    private $versions;
    /** @var \Berlioz\DocParser\File\FileInterface[][] Files */
    private $files;

    /**
     * FileSystemLoader constructor.
     *
     * @param string $basePath Base path
     * @param array  $options  Options
     *
     * @option string versions Versions to get for versioned documentation (empty = all)
     */
    public function __construct(string $basePath, array $options = [])
    {
        $this->basePath = $basePath;
        parent::__construct($options);
    }

    ////////////////////////
    /// LOADER INTERFACE ///
    ////////////////////////

    /**
     * @inheritdoc
     */
    public function getUniqId(): string
    {
        return sha1($this->getBasePath() . '-' . serialize($this->options));
    }

    /**
     * @inheritdoc
     */
    public function getVersions(): array
    {
        if (is_null($this->versions)) {
            if ($this->getOption('versioned', false)) {
                $this->versions = ['master'];
            } else {
                $this->versions = [];

                foreach (scandir($this->getBasePath()) as $filename) {
                    if (!in_array($filename, ['.', '..']) && is_dir($dirname = $this->getBasePath() . DIRECTORY_SEPARATOR . $filename)) {
                        $this->versions[] = basename($dirname);
                    }
                }

                // Filter branches
                if (is_array($this->getOption('versions'))) {
                    $this->versions =
                        array_filter(
                            $this->versions,
                            function ($value) {
                                return in_array($value, $this->getOption('versions'));
                            });
                }
            }
        }

        return $this->versions;
    }

    /**
     * @inheritdoc
     */
    public function getFiles(?string $version): array
    {
        if (is_null($version)) {
            $version = '';
        }

        if (!isset($this->files[$version])) {
            $path = $this->getBasePath();
            if ($this->getOption('versioned', false)) {
                $path .= sprintf('/%s', $version);
            }

            $this->files[$version] = [];
            $filenames = $this->scandir($path);

            foreach ($filenames as $filename) {
                $this->files[$version][] = $this->getFile($version, $filename);
            }
        }

        return $this->files[$version];
    }

    /////////////////////
    /// UTILS METHODS ///
    /////////////////////

    /**
     * Get base path.
     *
     * @return string
     */
    private function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Dir to array.
     *
     * @param string $dir    Directory to scan
     * @param string $prefix Prefix
     *
     * @return array
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    private function scandir(string $dir, string $prefix = '')
    {
        $files = [];

        foreach (scandir($dir) as $filename) {
            $fullFilename = rtrim($dir, '\\/') . '/' . $filename;
            $partialFilename = rtrim($prefix, '\\/') . '/' . $filename;

            if (is_file($fullFilename) && is_readable($fullFilename)) {
                if ($this->testFilter($fullFilename)) {
                    $files[] = str_replace('\\', '/', $partialFilename);
                }
            } else {
                if (!in_array($filename, ['.', '..']) && is_dir($fullFilename)) {
                    $files = array_merge($files, $this->scandir($fullFilename, $partialFilename));
                }
            }
        }

        return $files;
    }

    /**
     * Get file.
     *
     * @param string $version
     * @param string $path
     *
     * @return \Berlioz\DocParser\File\FileInterface
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    public function getFile(string $version, string $path): FileInterface
    {
        $fullPath = $this->getBasePath();
        if ($this->getOption('versioned', false)) {
            $fullPath .= sprintf('/%s', $version);
        }
        $fullPath = sprintf('%s/%s', $fullPath, ltrim($path, '\\/'));

        if (($content = file_get_contents($fullPath)) === false) {
            throw new LoaderException(sprintf('Unable to load file "%s", in "%s" directory', $path, $this->getBasePath()));
        }

        // Mime of file
        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->buffer($content);

        // File
        $rawFile = new RawFile();
        $rawFile->setHash(sha1_file($fullPath))
                ->setFilename($path)
                ->setContent($content)
                ->setMime($mime)
                ->setDatetime((new \DateTime)->setTimestamp(filemtime($fullPath)));

        return $rawFile;
    }
}