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

class Entry implements EntryIterableInterface, SummaryInterface
{
    use EntryIterable;

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
    public function __construct(
        private string $title,
        private ?string $path = null
    ) {
    }

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo(): array
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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
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
     * @param string|null $path
     */
    public function setPath(?string $path): void
    {
        $this->path = $path;
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
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
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
     */
    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }

    /**
     * Count visible entries.
     *
     * @param bool $value Value of visibility to count (default: true)
     *
     * @return int
     */
    public function countVisible(bool $value = true): int
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
     */
    public function setVisible(bool $visible, bool $recursive = false): void
    {
        $this->visible = $visible;

        // Set active
        if ($recursive) {
            if (null !== ($parent = $this->getParent()) && $parent instanceof Entry) {
                $parent->setVisible($visible, $recursive);
            }
        }
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
     */
    public function setActive(bool $active, bool $recursive = false): void
    {
        $this->active = $active;

        // Set active
        if ($recursive) {
            if (($parent = $this->getParent()) instanceof Entry) {
                $parent->setActive($active, $recursive);
            }
        }
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
     */
    public function setParent(?Entry $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get previous entry.
     *
     * @return Entry|null
     */
    public function getPrev(): ?Entry
    {
        if (null === $this->getParent()) {
            return null;
        }
        $prev = null;

        foreach ($this->getParent()->getIterator() as $sibling) {
            if ($sibling === $this) {
                return $prev;
            }

            // Track last seen sibling until we hit $this
            $prev = $sibling;
        }

        return null;
    }

    /**
     * Get next entry.
     *
     * @return Entry|null
     */
    public function getNext(): ?Entry
    {
        if (null === $this->getParent()) {
            return null;
        }
        $found = false;

        foreach ($this->getParent()->getIterator() as $sibling) {
            if (true === $found) {
                return $sibling;
            }

            if ($sibling === $this) {
                $found = true;
            }
        }

        return null;
    }
}