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

/**
 * Class DocSummary.
 *
 * @package Berlioz\DocParser\Doc
 */
class DocSummary extends PageSummary
{
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

        for ($i = 0; $i < $nbEntries; $i++) {
            $entry = $parentEntry->getEntryByTitle($breadcrumb[$i]);

            // Create if not exists or it's the last of list
            if (null === $entry) {
                $entry = new Entry($breadcrumb[$i]);
                $parentEntry->addEntry($entry);
            }

            // Define url and order if it's last element
            if ($i + 1 == $nbEntries) {
                $summaryOrder = $page->getMeta('summary-order');
                if (!empty($summaryOrder)) {
                    $summaryOrder = (int)$summaryOrder;
                }

                $entryVisible =
                    (bool)(
                        filter_var(
                            $page->getMeta('summary-visible', true),
                            FILTER_VALIDATE_BOOLEAN,
                            FILTER_NULL_ON_FAILURE
                        ) ?? false
                    );

                $entry
                    ->setPath($page->getPath())
                    ->setOrder($summaryOrder ?: null)
                    ->setVisible($entryVisible, $entryVisible);
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
     * @return array|null
     */
    private function getPageBreadcrumb(Page $page): ?array
    {
        if (null === ($breadcrumb = $page->getMeta('breadcrumb'))) {
            return null;
        }

        $breadcrumb = explode(';', $breadcrumb);
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
        $breadcrumb = array_filter($breadcrumb);

        return $breadcrumb;
    }
}