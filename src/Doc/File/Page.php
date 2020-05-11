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

use Berlioz\DocParser\Doc\PageSummary;
use DateTimeInterface;

/**
 * Class Page.
 *
 * @package Berlioz\DocParser\File
 */
class Page extends RawFile
{
    protected ?string $title = null;
    protected ?string $description = null;
    protected array $metas = [];
    protected PageSummary $summary;

    /**
     * Page constructor.
     *
     * @param resource $stream
     * @param string $filename
     * @param string|null $mime
     * @param DateTimeInterface|null $datetime
     */
    public function __construct(
        $stream,
        string $filename,
        ?string $mime = null,
        ?DateTimeInterface $datetime = null
    ) {
        parent::__construct($stream, $filename, $mime, $datetime);

        $this->summary = new PageSummary();
    }

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $info = parent::__debugInfo();

        $info['title'] = $this->title;
        $info['description'] = $this->description;
        $info['metas'] = $this->metas;
        $info['summary'] = $this->summary;
        $info['contents'] = $this->getContents();

        return $info;
    }

    /**
     * @inheritDoc
     */
    public function __serialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'metas' => $this->metas,
            'summary' => $this->summary,
            'parent_data' => parent::__serialize(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->metas = $data['metas'];
        $this->summary = $data['summary'];

        parent::__unserialize($data['parent_data']);
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
        if (null !== $this->title) {
            return $this->title;
        }

        return $this->getMeta('title');
    }

    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return static
     */
    public function setTitle(?string $title): Page
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        if (null !== $this->description) {
            return $this->description;
        }

        return $this->getMeta('description');
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return static
     */
    public function setDescription(?string $description): Page
    {
        $this->description = $description;

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
     * @param mixed $default
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
     * @inheritDoc
     */
    public function getPath(): string
    {
        $slug = $this->getMeta('slug');

        if (empty($slug)) {
            $slug = basename($this->getFilename());
            if (false !== ($extensionPosition = strrpos($slug, '.'))) {
                $slug = substr($slug, 0, $extensionPosition);
            }
        }

        $dirname = dirname($this->getFilename());
        $dirname = $dirname == '.' ? '' : $dirname;
        $path = str_replace('\\', '/', $dirname);

        return ltrim(sprintf('%s/%s', $path, urlencode($slug)), '/');
    }

    /**
     * Get summary.
     *
     * @return PageSummary
     */
    public function getSummary(): PageSummary
    {
        return $this->summary;
    }

    /**
     * Set summary.
     *
     * @param PageSummary $summary
     *
     * @return static
     */
    public function setSummary(PageSummary $summary): Page
    {
        $this->summary = $summary;

        return $this;
    }
}