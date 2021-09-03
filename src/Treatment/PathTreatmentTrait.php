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

trait PathTreatmentTrait
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

        if (!str_starts_with($dstPath, '/')) {
            // Complete absolute link
            if (str_starts_with($dstPath, './')) {
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

        if (!str_contains($finalPath, './')) {
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

        if (str_starts_with($srcPath, '..')) {
            throw new InvalidArgumentException('Source path must be a relative path');
        }
        if (str_starts_with($srcPath, './')) {
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
            if (!isset($dstPath[$i]) || $srcPath[$i] !== $dstPath[$i] || $differentDepthPath > 0) {
                $differentDepthPath++;
            }
        }

        $relativePath = '';
        if ($differentDepthPath > 0) {
            $relativePath .= str_repeat('../', $differentDepthPath);
            $relativePath .= implode('/', array_slice($dstPath, min($dstDepth, $differentDepthPath) - 1));
        }
        if ($differentDepthPath === 0) {
            $relativePath .= './';
            $relativePath .= implode('/', array_slice($dstPath, $srcDepth, $dstDepth));
        }

        // Add file to relative path
        if (null !== $dstFilename) {
            $relativePath .= '/' . $dstFilename;
        }

        return preg_replace('#/{2,}#', '/', $relativePath);
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

        return preg_replace('#/{2,}#', '', $path);
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