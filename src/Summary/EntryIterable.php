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

trait EntryIterable
{
    /** @var \Berlioz\DocParser\Summary\Entry[] */
    protected $entries = [];

    /**
     * Create new iterator.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->entries);
    }

    /**
     * Count entries.
     *
     * @return int
     */
    public function count()
    {
        return count($this->entries);
    }

    /**
     * Order entries.
     */
    protected function orderEntries()
    {
        usort($this->entries,
            function ($el1, $el2) {
                /** @var \Berlioz\DocParser\Summary\Entry $el1 */
                /** @var \Berlioz\DocParser\Summary\Entry $el2 */
                if ($el1->getOrder() === $el2->getOrder()) {
                    if (is_null($el1->getOrder())) {
                        return 0;
                    } else {
                        return strcasecmp($el1->getTitle(), $el2->getTitle());
                    }
                } else {
                    if (is_null($el1->getOrder()) && !is_null($el2->getOrder())) {
                        return 1;
                    } elseif (!is_null($el1->getOrder()) && is_null($el2->getOrder())) {
                        return -1;
                    } else {
                        return ($el1->getOrder() < $el2->getOrder()) ? -1 : 1;
                    }
                }
            });
    }

    /**
     * Get entry by title.
     *
     * @param string $title
     *
     * @return \Berlioz\DocParser\Summary\Entry|null
     */
    public function getEntryByTitle(string $title)
    {
        foreach ($this->entries as $entry) {
            if ($entry->getTitle() == $title) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Get entries.
     *
     * @return \Berlioz\DocParser\Summary\Entry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Set entries.
     *
     * @param \Berlioz\DocParser\Summary\Entry[] $entries
     *
     * @return static
     */
    public function setEntries(array $entries)
    {
        // Filter entries
        $entries =
            array_filter(
                $entries,
                function ($value) {
                    return $value instanceof Entry;
                });

        // Set entries
        $this->entries = $entries;

        // Set parent
        foreach ($entries as $entry) {
            if ($this instanceof Entry) {
                $entry->setParentEntry($this);
            } else {
                $entry->setParentEntry(null);
            }
        }

        // Order
        $this->orderEntries();

        return $this;
    }

    /**
     * Add entry.
     *
     * @param \Berlioz\DocParser\Summary\Entry $entry
     *
     * @return static
     */
    public function addEntry(Entry $entry)
    {
        $this->entries[] = $entry;
        if ($this instanceof Entry) {
            $entry->setParentEntry($this);
        } else {
            $entry->setParentEntry(null);
        }

        // Order
        $this->orderEntries();

        return $this;
    }
}