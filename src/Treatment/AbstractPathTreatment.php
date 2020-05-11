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

namespace Berlioz\DocParser\Treatment;

use InvalidArgumentException;

abstract class AbstractPathTreatment
{
    /**
     * Resolve absolute path.
     *
     * @param string $srcPath
     * @param string $dstPath
     *
     * @return string|null
     */
    public function resolveAbsolutePath(string $srcPath, string $dstPath): ?string
    {
        $srcPath = $this->uniformizePathSeparator($srcPath);
        $dstPath = $this->uniformizePathSeparator($dstPath);
        $finalPath = $dstPath;

        if (substr($dstPath, 0, 1) !== '/') {
            // Complete absolute link
            if (substr($dstPath, 0, 2) === './') {
                $dstPath = substr($dstPath, 2);
            }

            // Unification of directories separators
            $finalPath = $this->uniformizePathSeparator(dirname($srcPath));
            if ($finalPath === '.') {
                $finalPath = '';
            }

            // Concatenation
            $finalPath = sprintf('%s/%s', $finalPath, $dstPath);
        }

        // Replacement of './'
        $finalPath = str_replace('/./', '/', $finalPath);

        // Replacement of '../'
        do {
            $finalPath = preg_replace('#(/|^)([^\\\/?%*:|"<>.]+)/../#', '/', $finalPath, -1, $nbReplacements);
        } while ($nbReplacements > 0);

        if (strpos($finalPath, './') === false) {
            return ltrim($finalPath, '/');
        }

        return null;
    }

    /**
     * Resolve relative path.
     *
     * @param string $srcPath
     * @param string $dstPath
     *
     * @return string
     */
    public function resolveRelativePath(string $srcPath, string $dstPath): string
    {
        $srcPath = $this->resolveAbsolutePath('/', $srcPath);
        $dstPath = $this->resolveAbsolutePath($srcPath, $dstPath);

        if (substr($srcPath, 0, 2) === '..') {
            throw new InvalidArgumentException('Source path must be a relative path');
        }
        if (substr($srcPath, 0, 2) === './') {
            $srcPath = substr($srcPath, 2);
        }

        $srcPath = explode('/', $srcPath);
        $dstPath = explode('/', $dstPath);

        // Already relative?
        if (in_array(reset($dstPath), ['.', '..'])) {
            return implode('/', $dstPath);
        }

        // Get filename of destination path
        $dstFilename = $this->extractFilename($dstPath);
        $this->extractFilename($srcPath);

        $srcDepth = count($srcPath);
        $dstDepth = count($dstPath);
        $differentDepthPath = 0;

        for ($i = 0; $i < $srcDepth; $i++) {
            if (!isset($dstPath[$i]) || $srcPath[$i] !== $dstPath[$i]) {
                $differentDepthPath++;
            }
        }

        $relativePath = '';
        if ($differentDepthPath > 0) {
            $relativePath .= str_repeat('../', $differentDepthPath);
            $relativePath .= implode('/', (array)array_slice($dstPath, $differentDepthPath - 1, $dstDepth));
        }
        if ($differentDepthPath === 0) {
            $relativePath .= './';
            $relativePath .= implode('/', (array)array_slice($dstPath, $srcDepth, $dstDepth));
        }

        // Add file to relative path
        if (null !== $dstFilename) {
            $relativePath .= '/' . $dstFilename;
        }

        $relativePath = preg_replace('#/{2,}#', '/', $relativePath);

        return $relativePath;
    }

    /**
     * Uniformize path separator.
     *
     * @param string $path
     *
     * @return string
     */
    private function uniformizePathSeparator(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/{2,}#', '', $path);

        return $path;
    }

    /**
     * Extract filename.
     *
     * @param array $path
     *
     * @return string|null
     */
    private function extractFilename(array &$path): ?string
    {
        $filename = end($path) ?: null;

        if (null !== $filename) {
            unset($path[count($path) - 1]);
        }

        return $filename;
    }
}