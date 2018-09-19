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
use Berlioz\DocParser\Exception\DocParserException;
use Berlioz\DocParser\Generator;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream;
use Psr\Http\Message\ResponseInterface;

class RawFile implements FileInterface
{
    /** @var \Berlioz\DocParser\Documentation\DocumentationVersion Documentation version */
    protected $documentationVersion;
    /** @var string Hash */
    protected $hash;
    /** @var string Original filename */
    protected $filename;
    /** @var string URL path */
    protected $url_path;
    /** @var string Mime */
    protected $mime;
    /** @var \DateTime Date time */
    protected $datetime;
    /** @var string Content */
    protected $content;

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['hash'     => $this->hash,
                'filename' => $this->filename,
                'url_path' => $this->url_path,
                'mime'     => $this->mime,
                'datetime' => $this->datetime];
    }

    /**
     * Get documentation version.
     *
     * @return \Berlioz\DocParser\Documentation\DocumentationVersion
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getDocumentationVersion(): DocumentationVersion
    {
        if (is_null($this->documentationVersion)) {
            throw new DocParserException('Missing documentation version object reference');
        }

        return $this->documentationVersion;
    }

    /**
     * Set documentation version.
     *
     * @param \Berlioz\DocParser\Documentation\DocumentationVersion $documentationVersion
     *
     * @return static
     */
    public function setDocumentationVersion(DocumentationVersion $documentationVersion)
    {
        $this->documentationVersion = $documentationVersion;

        return $this;
    }

    ////////////////////
    /// SERIALIZABLE ///
    ////////////////////

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize(['hash'     => $this->hash,
                          'filename' => $this->filename,
                          'url_path' => $this->url_path,
                          'mime'     => $this->mime,
                          'datetime' => $this->datetime]);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->hash = $unserialized['hash'];
        $this->filename = $unserialized['filename'];
        $this->url_path = $unserialized['url_path'];
        $this->mime = $unserialized['mime'];
        $this->datetime = $unserialized['datetime'];
    }

    //////////////////////
    /// FILE INTERFACE ///
    //////////////////////

    /**
     * @inheritdoc
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function __toString(): string
    {
        return $this->getContent() ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @inheritdoc
     */
    public function setHash(string $hash): FileInterface
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @inheritdoc
     */
    public function setFilename(string $filename): FileInterface
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUrlPath(): string
    {
        return mb_strtolower($this->url_path ?? str_replace('\\', '/', $this->getFilename()));
    }

    /**
     * @inheritdoc
     */
    public function getRelativeUrlPathFor($link): string
    {
        if ($link instanceof FileInterface) {
            $link = $link->getUrlPath();
        }

        $link = explode('#', $link);
        $anchor = $link[1] ?? null;
        $link = $link[0];

        $relativePath = Generator::resolveRelativePath($this->getUrlPath(), $link);

        if ($relativePath == './' . basename($this->getUrlPath())) {
            $relativePath = '';
        }

        $relativePath .= (!empty($anchor) ? '#' . $anchor : '');

        return mb_strtolower($relativePath);
    }

    /**
     * @inheritdoc
     */
    public function setUrlPath(string $url_path): FileInterface
    {
        $this->url_path = $url_path;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @inheritdoc
     */
    public function setMime(string $mime): FileInterface
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    /**
     * @inheritdoc
     */
    public function setDatetime(\DateTime $datetime): FileInterface
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getContent(): string
    {
        if (is_null($this->content)) {
            $this->getDocumentationVersion()->getDocumentation()->getCache()->getFile($this);
        }

        return $this->content;
    }

    /**
     * @inheritdoc
     */
    public function setContent(string $content): FileInterface
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function response(?ResponseInterface $response = null): ResponseInterface
    {
        if (is_null($response)) {
            $response = new Response();
        }

        // Body content
        $stream = new Stream();
        if ($this instanceof ParsedFileInterface) {
            $stream->write($this->getParsedContent());
        } else {
            $stream->write($this->getContent());
        }

        return $response->withBody($stream)
                        ->withHeader('Content-Type', $this->getMime());
    }
}