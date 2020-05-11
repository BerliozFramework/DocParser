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

namespace Berlioz\DocParser\Doc\Summary;

/**
 * Class Entry.
 *
 * @package Berlioz\DocParser\Doc\Summary
 */
class Entry implements EntryIterableInterface
{
    use EntryIterable;

    private string $title;
    private ?string $path;
    private ?string $id = null;
    private ?int $order = null;
    private bool $visible = true;
    private bool $active = false;
    private ?Entry $parent = null;

    /**
     * Entry constructor.
     *
     * @param string $title
     * @param string|null $path
     */
    public function __construct(string $title, ?string $path = null)
    {
        $this->title = $title;
        $this->path = $path;
    }

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'title' => $this->title,
            'path' => $this->path,
            'id' => $this->id,
            'order' => $this->order,
            'visible' => $this->visible,
            'active' => $this->active,
            'entries' => $this->entries
        ];
    }

    /**
     * @inheritDoc
     */
    public function __serialize(): array
    {
        return [
            'title' => $this->title,
            'path' => $this->path,
            'id' => $this->id,
            'order' => $this->order,
            'visible' => $this->visible,
            'active' => $this->active,
            'entries' => $this->entries
        ];
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        $this->title = $data['title'];
        $this->path = $data['path'];
        $this->id = $data['id'];
        $this->order = $data['order'];
        $this->visible = $data['visible'];
        $this->active = $data['active'];
        $this->entries = $data['entries'];

        /** @var Entry $entry */
        foreach ($this->entries as $entry) {
            $entry->setParent($this);
        }
    }

    /**
     * Get title.
     *
     * @return string
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
    public function setTitle(string $title): Entry
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get path.
     *
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Set path.
     *
     * @param null|string $path
     *
     * @return static
     */
    public function setPath(string $path): Entry
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get id.
     *
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param null|string $id
     *
     * @return static
     */
    public function setId(?string $id): Entry
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get order.
     *
     * @return int|null
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * Set order.
     *
     * @param int|null $order
     *
     * @return static
     */
    public function setOrder(?int $order): Entry
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Count visible entries.
     *
     * @param bool $value Value of visibility to count (default: true)
     *
     * @return int
     */
    public function countVisible(bool $value = true)
    {
        $nb = 0;

        /** @var Entry $entry */
        foreach ($this as $entry) {
            if ($entry->isVisible() == $value) {
                $nb++;
            }
        }

        return $nb;
    }

    /**
     * Is visible ?
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        if (null === $this->visible) {
            return true;
        }

        return $this->visible;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     * @param bool $recursive
     *
     * @return static
     */
    public function setVisible(bool $visible, bool $recursive = false): Entry
    {
        $this->visible = $visible;

        // Set active
        if ($recursive) {
            if (null !== ($parent = $this->getParent()) && $parent instanceof Entry) {
                $parent->setVisible($visible, $recursive);
            }
        }

        return $this;
    }

    /**
     * Is active?
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set active.
     *
     * @param bool $active
     * @param bool $recursive
     *
     * @return static
     */
    public function setActive(bool $active, bool $recursive = false): Entry
    {
        $this->active = $active;

        // Set active
        if ($recursive) {
            if (($parent = $this->getParent()) instanceof Entry) {
                $parent->setActive($active, $recursive);
            }
        }

        return $this;
    }

    /**
     * Get parent entry.
     *
     * @return Entry|null
     */
    public function getParent(): ?Entry
    {
        return $this->parent;
    }

    /**
     * Set parent entry.
     *
     * @param Entry|null $parent
     *
     * @return Entry
     */
    public function setParent(?Entry $parent): Entry
    {
        $this->parent = $parent;

        return $this;
    }
}