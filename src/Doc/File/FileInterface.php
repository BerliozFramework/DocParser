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

use DateTimeInterface;
use Psr\Http\Message\ResponseInterface;

interface FileInterface
{
    /**
     * __serialize() PHP method.
     *
     * @return array
     */
    public function __serialize(): array;

    /**
     * __unserialize() PHP method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void;

    /**
     * __toString() PHP method.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Get mime.
     *
     * @return string|null
     */
    public function getMime(): ?string;

    /**
     * Get date time.
     *
     * @return DateTimeInterface|null
     */
    public function getDatetime(): ?DateTimeInterface;

    /**
     * Get stream.
     *
     * @return resource
     */
    public function getStream();

    /**
     * Set stream.
     *
     * @param resource $stream
     */
    public function setStream($stream): void;

    /**
     * Get contents.
     *
     * @return string
     */
    public function getContents(): string;

    /**
     * Set contents.
     *
     * @param string $contents
     */
    public function setContents(string $contents): void;

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash(): string;

    /**
     * Get ResponseInterface object for this media.
     *
     * @param ResponseInterface|null $response
     *
     * @return ResponseInterface
     */
    public function response(?ResponseInterface $response = null): ResponseInterface;
}