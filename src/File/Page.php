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

use Berlioz\DocParser\Summary;

class Page extends RawFile implements ParsedFileInterface
{
    /** @var string Parsed content */
    protected $parsedContent;
    /** @var string Title */
    protected $title;
    /** @var array Meta data */
    protected $metas;
    /** @var \Berlioz\DocParser\Summary Summary */
    protected $summary;

    /**
     * Page constructor.
     *
     * @param \Berlioz\DocParser\File\RawFile|null $file
     *
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function __construct(?RawFile $file = null)
    {
        if (!is_null($file)) {
            $this->setHash($file->getHash());
            $this->setFilename($file->getFilename());
            $this->setUrlPath($file->getUrlPath());
            $this->setMime($file->getMime());
            $this->setDatetime($file->getDatetime());
            $this->setContent($file->getContent());
        }
    }

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
                'datetime' => $this->datetime,
                'title'    => $this->title,
                'metas'    => $this->metas,
                'summary'  => $this->summary];
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize(['hash'     => $this->hash,
                          'filename' => $this->filename,
                          'url_path' => $this->url_path,
                          'mime'     => $this->mime,
                          'datetime' => $this->datetime,
                          'title'    => $this->title,
                          'metas'    => $this->metas,
                          'summary'  => $this->summary]);
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
        $this->title = $unserialized['title'];
        $this->metas = $unserialized['metas'];
        $this->summary = $unserialized['summary'];
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function __toString(): string
    {
        return $this->getParsedContent() ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getUrlPath(): string
    {
        if (is_null($url = $this->getMeta('url'))) {
            $pathinfo = pathinfo($this->getFilename());

            $url = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
            $url = str_replace('\\', '/', $url);
            $url = rtrim($url, '/');
            $url = '/' . ltrim($url, '/');
        }

        return mb_strtolower($url);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    public function getParsedContent(): string
    {
        if (is_null($this->parsedContent)) {
            $this->getDocumentationVersion()->getDocumentation()->getCache()->getFile($this);
        }

        return $this->parsedContent ?? $this->content;
    }

    /**
     * @inheritdoc
     */
    public function setParsedContent(string $parsedContent): ParsedFileInterface
    {
        $this->parsedContent = $parsedContent;

        return $this;
    }

    //////////////////////
    /// PAGE SPECIFICS ///
    //////////////////////

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return static
     */
    public function setTitle(string $title): Page
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get metas.
     *
     * @return array
     */
    public function getMetas(): array
    {
        return $this->metas ?? [];
    }

    /**
     * Get meta.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public function getMeta($name, $default = null)
    {
        return $this->metas[$name] ?? $default;
    }

    /**
     * Set metas.
     *
     * @param array $metas
     *
     * @return static
     */
    public function setMetas(array $metas): Page
    {
        $this->metas = $metas;

        return $this;
    }

    /**
     * Get summary.
     *
     * @return \Berlioz\DocParser\Summary
     */
    public function getSummary(): Summary
    {
        if (is_null($this->summary)) {
            $this->summary = new Summary();
        }

        return $this->summary;
    }

    /**
     * Set summary.
     *
     * @param \Berlioz\DocParser\Summary $summary
     *
     * @return static
     */
    public function setSummary(Summary $summary): Page
    {
        $this->summary = $summary;

        return $this;
    }
}