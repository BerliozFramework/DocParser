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

namespace Berlioz\DocParser\File;

use Berlioz\DocParser\Documentation\DocumentationVersion;
use Psr\Http\Message\ResponseInterface;

interface FileInterface extends \Serializable
{
    /**
     * Get documentation version.
     *
     * @return \Berlioz\DocParser\Documentation\DocumentationVersion
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getDocumentationVersion(): DocumentationVersion;

    /**
     * Set documentation version.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentationVersion
     *
     * @return static
     */
    public function setDocumentationVersion(DocumentationVersion $documentationVersion);

    /**
     * __toString() PHP method.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash(): string;

    /**
     * Set hash.
     *
     * @param string $hash
     *
     * @return static
     */
    public function setHash(string $hash): FileInterface;

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return static
     */
    public function setFilename(string $filename): FileInterface;

    /**
     * Get url path.
     *
     * @return string
     */
    public function getUrlPath(): string;

    /**
     * Get absolute url path.
     *
     * @param \Berlioz\DocParser\File\FileInterface|string $link
     *
     * @return string
     */
    public function getRelativeUrlPathFor($link): string;

    /**
     * Set url path.
     *
     * @param string $url_path
     *
     * @return \Berlioz\DocParser\File\FileInterface
     */
    public function setUrlPath(string $url_path): FileInterface;

    /**
     * Get mime.
     *
     * @return string
     */
    public function getMime(): string;

    /**
     * Set mime.
     *
     * @param string $mime
     *
     * @return static
     */
    public function setMime(string $mime): FileInterface;

    /**
     * Get date time.
     *
     * @return \DateTime
     */
    public function getDatetime(): \DateTime;

    /**
     * Set date time.
     *
     * @param \DateTime $datetime
     *
     * @return static
     */
    public function setDatetime(\DateTime $datetime): FileInterface;

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent(string $content): FileInterface;

    /**
     * Get ResponseInterface object for this media.
     *
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function response(?ResponseInterface $response = null): ResponseInterface;
}