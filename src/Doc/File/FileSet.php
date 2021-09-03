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

namespace Berlioz\DocParser\Doc\File;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class FileSet implements IteratorAggregate, Countable
{
    /** @var FileInterface[] Files */
    private array $files = [];

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return ['files' => $this->files];
    }

    /**
     * __serialize() PHP method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return ['files' => $this->files];
    }

    /**
     * __unserialize() PHP method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->files = $data['files'];
    }

    ///////////////////////////////////
    /// IteratorAggregate interface ///
    ///////////////////////////////////

    /**
     * Implementation of \IteratorAggregate interface.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->files);
    }

    ///////////////////////////
    /// Countable interface ///
    ///////////////////////////

    /**
     * Count files.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->files);
    }

    /////////////
    /// Files ///
    /////////////

    /**
     * Add file.
     *
     * @param FileInterface $file
     *
     * @return static
     */
    public function addFile(FileInterface $file): static
    {
        $this->files[] = $file;

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
        if (null === $classFilter) {
            return $this->files;
        }

        return
            array_filter(
                $this->files,
                function ($value) use ($classFilter) {
                    return is_a($value, $classFilter, true);
                }
            );
    }

    /**
     * Filter.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function filter(callable $callback): static
    {
        $fileSet = new FileSet();

        foreach ($this as $file) {
            if ($callback($file)) {
                $fileSet->addFile($file);
            }
        }

        return $fileSet;
    }

    /**
     * Find by filename.
     *
     * @param string $filename
     *
     * @return FileInterface|null
     */
    public function findByFilename(string $filename): ?FileInterface
    {
        $filename = $this->normalizePath($filename);

        /** @var FileInterface $file */
        foreach ($this as $file) {
            if ($this->normalizePath($file->getFilename()) == $filename) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Find file by path.
     *
     * @param string $path
     *
     * @return FileInterface|null
     */
    public function findByPath(string $path): ?FileInterface
    {
        $path = $this->normalizePath($path);

        /** @var FileInterface $file */
        foreach ($this as $file) {
            if ($file->getPath() == $path) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Normalize path.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_contains($path, '#')) {
            return explode('#', $path, 2)[0];
        }

        return $path;
    }
}