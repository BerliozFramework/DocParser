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

namespace Berlioz\DocParser\Doc;

use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Doc\Summary\Entry;

class DocSummary extends PageSummary
{
    private ?Entry $active = null;

    /**
     * Get active entry.
     *
     * @return Entry|null
     */
    public function getActive(): ?Entry
    {
        return $this->active;
    }

    /**
     * Set active entry.
     *
     * @param Entry|null $entry
     * @param bool $recursive
     *
     * @return void
     */
    public function setActive(?Entry $entry, bool $recursive = true): void
    {
        $entry?->setActive(true, $recursive);
        $this->active = $entry;
    }

    /**
     * Find by page.
     *
     * @param Page $page
     *
     * @return Entry|null
     */
    public function findByPage(Page $page): ?Entry
    {
        $breadcrumb = $this->getPageBreadcrumb($page);

        if (empty($breadcrumb)) {
            return null;
        }

        return $this->findByBreadcrumb($breadcrumb);
    }

    /**
     * Add page to summary.
     *
     * @param Page $page
     */
    public function addPage(Page $page): void
    {
        if (null === ($breadcrumb = $this->getPageBreadcrumb($page))) {
            return;
        }

        $nbEntries = count($breadcrumb);
        $parentEntry = $this;

        // Summary order
        $summaryOrder = $page->getMeta('summary-order', '');
        !is_array($summaryOrder) && $summaryOrder = explode(';', (string)$summaryOrder);
        array_walk($summaryOrder, fn(&$value) => $value = (int)trim($value));
        array_walk($summaryOrder, fn(&$value) => $value = empty($value) ? null : $value);
        $summaryOrder = array_pad($summaryOrder, 0 - $nbEntries, null);
        $summaryOrder = array_slice($summaryOrder, 0, $nbEntries);

        for ($i = 0; $i < $nbEntries; $i++) {
            $entry = $parentEntry->getEntryByTitle($breadcrumb[$i]);

            // Create if not exists or it's the last of list
            if (null === $entry) {
                $entry = new Entry($breadcrumb[$i]);
                $parentEntry->addEntry($entry);
            }

            // Define url and order if it's last element
            if ($i + 1 == $nbEntries) {
                $entryVisible = (bool)$page->getMeta('summary-visible', true);
                $entry->setPath($page->getPath());
                $entry->setVisible($entryVisible, $entryVisible);
            }

            if (isset($summaryOrder[$i])) {
                $entry->setOrder($summaryOrder[$i]);
            }

            $parentEntry = $entry;
        }

        $this->orderEntries();
    }

    /**
     * Find by breadcrumb.
     *
     * @param array $breadcrumb
     *
     * @return Entry|null
     */
    private function findByBreadcrumb(array $breadcrumb): ?Entry
    {
        $breadcrumb = $this->filterBreadcrumb($breadcrumb);
        $nbEntries = count($breadcrumb);

        if ($nbEntries === 0) {
            return null;
        }

        // Search
        $iEntry = 0;
        $entry = $this;
        do {
            $entry = $entry->getEntryByTitle($breadcrumb[$iEntry]);
            $iEntry++;
        } while (!is_null($entry) && $iEntry < $nbEntries);

        return $entry;
    }

    /**
     * Get page breadcrumb.
     *
     * @param Page $page
     *
     * @return array|null
     */
    private function getPageBreadcrumb(Page $page): ?array
    {
        if (null === ($breadcrumb = $page->getMeta('breadcrumb'))) {
            return null;
        }

        !is_array($breadcrumb) && $breadcrumb = explode(';', $breadcrumb);
        $breadcrumb = $this->filterBreadcrumb($breadcrumb);

        if (empty($breadcrumb)) {
            return null;
        }

        return $breadcrumb;
    }

    /**
     * Filter breadcrumb.
     *
     * @param array $breadcrumb
     *
     * @return array
     */
    private function filterBreadcrumb(array $breadcrumb): array
    {
        array_walk($breadcrumb, fn(&$value) => $value = trim($value));

        return array_filter($breadcrumb);
    }
}