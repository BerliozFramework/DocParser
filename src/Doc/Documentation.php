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

namespace Berlioz\DocParser\Doc;

use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\FileSet;
use Berlioz\DocParser\Doc\File\Page;

/**
 * Class Documentation.
 *
 * @package Berlioz\DocParser
 */
class Documentation
{
    private string $version;
    private DocSummary $summary;
    private FileSet $files;

    /**
     * DocumentationVersion constructor.
     *
     * @param string $version
     */
    public function __construct(string $version)
    {
        $this->version = $version;
        $this->summary = new DocSummary();
        $this->files = new FileSet();
    }

    /**
     * __debugInfo() PHP method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'version' => $this->version,
            'summary' => $this->summary,
            'files' => $this->files
        ];
    }

    /**
     * __serialize() PHP method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'version' => $this->version,
            'summary' => $this->summary,
            'files' => $this->files
        ];
    }

    /**
     * __unserialize() PHP method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->version = $data['version'];
        $this->summary = $data['summary'];
        $this->files = $data['files'];
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
     * Get summary.
     *
     * @return DocSummary
     */
    public function getSummary(): DocSummary
    {
        return $this->summary;
    }

    /**
     * Get files.
     *
     * @param callable|null $filter
     *
     * @return FileSet
     */
    public function getFiles(?callable $filter = null): FileSet
    {
        if (null === $filter) {
            return $this->files;
        }

        return $this->files->filter($filter);
    }

    /**
     * Handle.
     *
     * @param string $path
     *
     * @return FileInterface|null
     */
    public function handle(string $path): ?FileInterface
    {
        $file = $this->getFiles()->findByPath($path);

        if ($file instanceof Page) {
            $summaryEntry = $this->getSummary()->findByPage($file);

            if (null !== $summaryEntry) {
                $summaryEntry->setActive(true, true);
            }
        }

        return $file;
    }
}