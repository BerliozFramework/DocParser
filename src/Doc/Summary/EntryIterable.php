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

use ArrayIterator;

trait EntryIterable
{
    protected array $entries = [];

    /**
     * Create new iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->entries);
    }

    /**
     * Count entries.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * Count recursive entries.
     *
     * @return int
     */
    public function countRecursive(): int
    {
        $count = 0;
        foreach ($this as $entry) {
            $count++;

            if ($entry instanceof EntryIterableInterface) {
                $count += $entry->countRecursive();
            }
        }

        return $count;
    }

    /**
     * Order entries.
     */
    public function orderEntries(): void
    {
        // Order entries
        usort(
            $this->entries,
            function ($el1, $el2) {
                /** @var Entry $el1 */
                /** @var Entry $el2 */
                if ($el1->getOrder() === $el2->getOrder()) {
                    if (is_null($el1->getOrder())) {
                        return 0;
                    }

                    return strcasecmp($el1->getTitle(), $el2->getTitle());
                }

                if (null === $el1->getOrder() && null !== $el2->getOrder()) {
                    return 1;
                }

                if (null !== $el1->getOrder() && null === $el2->getOrder()) {
                    return -1;
                }

                return ($el1->getOrder() < $el2->getOrder()) ? -1 : 1;
            }
        );

        // Children
        foreach ($this->entries as $entry) {
            $entry->orderEntries();
        }
    }

    /**
     * Get entry by title.
     *
     * @param string $title
     *
     * @return Entry|null
     */
    public function getEntryByTitle(string $title): ?Entry
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
     * @return Entry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Add entry.
     *
     * @param Entry $entry
     */
    public function addEntry(Entry $entry): void
    {
        $entry->setParent(null);
        $this->entries[] = $entry;

        if ($this instanceof Entry) {
            $entry->setParent($this);
        }
    }
}