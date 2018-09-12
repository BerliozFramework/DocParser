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

namespace Berlioz\DocParser\Summary;

class Entry implements EntryIterableInterface
{
    use EntryIterable;
    /** @var string Title */
    private $title;
    /** @var string|null Url */
    private $url;
    /** @var string|null Id of element */
    private $id;
    /** @var int|null Order */
    private $order;
    /** @var bool Visible ? */
    private $visible = false;
    /** @var bool Selected ? */
    private $selected = false;
    /** @var \Berlioz\DocParser\Summary\Entry Parent */
    private $parentEntry;

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['title'    => $this->title,
                'url'      => $this->url,
                'id'       => $this->id,
                'order'    => $this->order,
                'visible'  => $this->visible,
                'selected' => $this->selected,
                'entries'  => $this->entries];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['title'    => $this->title,
                          'url'      => $this->url,
                          'id'       => $this->id,
                          'order'    => $this->order,
                          'visible'  => $this->visible,
                          'selected' => $this->selected,
                          'entries'  => $this->entries]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->title = $unserialized['title'];
        $this->url = $unserialized['url'];
        $this->id = $unserialized['id'];
        $this->order = $unserialized['order'];
        $this->visible = $unserialized['visible'];
        $this->selected = $unserialized['selected'];
        $this->entries = $unserialized['entries'];

        foreach ($this->entries as $entry) {
            $entry->setParentEntry($this);
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
     * Get url.
     *
     * @return null|string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     * @param null|string $url
     *
     * @return static
     */
    public function setUrl(string $url): Entry
    {
        $this->url = $url;

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
     * Get order of entry.
     *
     * @return int|null
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * Set order of entry.
     *
     * @param int|null $order
     *
     * @return static
     */
    public function setOrder(?int $order): Entry
    {
        $oldOrder = $this->order;
        $this->order = $order;

        // Order parent
        if ($oldOrder != $this->order && !is_null($this->getParentEntry())) {
            $this->getParentEntry()->orderEntries();
        }

        return $this;
    }

    /**
     * Is visible ?
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible ?? false;
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

        // Set selected
        if ($recursive) {
            if (!is_null($parentEntry = $this->getParentEntry()) && $parentEntry instanceof Entry) {
                $parentEntry->setVisible($visible, $recursive);
            }
        }

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

        /** @var \Berlioz\DocParser\Summary\Entry $entry */
        foreach ($this as $entry) {
            $nb += $entry->isVisible() == $value ? 1 : 0;
        }

        return $nb;
    }

    /**
     * Is selected ?
     *
     * @return bool
     */
    public function isSelected(): bool
    {
        return $this->selected;
    }

    /**
     * Set selected.
     *
     * @param bool $selected
     * @param bool $recursive
     *
     * @return static
     */
    public function setSelected(bool $selected, bool $recursive = false): Entry
    {
        $this->selected = $selected;

        // Set selected
        if ($recursive) {
            if (!is_null($parentEntry = $this->getParentEntry()) && $parentEntry instanceof Entry) {
                $parentEntry->setSelected($selected, $recursive);
            }
        }

        return $this;
    }

    /**
     * Get parent entry.
     *
     * @return \Berlioz\DocParser\Summary\Entry|null
     */
    public function getParentEntry(): ?Entry
    {
        return $this->parentEntry;
    }

    /**
     * @param \Berlioz\DocParser\Summary\Entry|null $parentEntry
     *
     * @return Entry
     */
    public function setParentEntry(?Entry $parentEntry): Entry
    {
        $this->parentEntry = $parentEntry;

        return $this;
    }
}