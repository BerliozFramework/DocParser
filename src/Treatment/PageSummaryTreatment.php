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

namespace Berlioz\DocParser\Treatment;

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Doc\Summary\Entry;
use Berlioz\HtmlSelector\Exception\QueryException;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\Query;

/**
 * Class PageSummaryTreatment.
 *
 * @package Berlioz\DocParser\Treatment
 */
class PageSummaryTreatment implements TreatmentInterface
{
    /**
     * @inheritDoc
     * @throws SelectorException
     * @throws QueryException
     */
    public function handle(Documentation $documentation): void
    {
        /** @var Page $page */
        foreach ($documentation->getFiles()->filter(fn($file) => $file instanceof Page) as $page) {
            $this->makePageSummary($page);
        }
    }

    /**
     * Get summary for a page.
     *
     * @param Page $page
     *
     * @throws SelectorException
     * @throws QueryException
     */
    public function makePageSummary(Page $page): void
    {
        $summary = $page->getSummary();
        $query = Query::loadHtml($page->getContents());
        $ids = [];
        $headers = $query->find(':header:not(h1)');

        $entries = [];
        foreach ($headers as $header) {
            // Header level
            $headerLevel = $this->getHeaderLevel($header);

            // Remove old parent
            for ($i = count($entries) - 1; $i >= 0; $i--) {
                if ($entries[$i]['level'] >= $headerLevel) {
                    array_pop($entries);
                }
            }

            // Get id of header
            if (null === ($id = $header->attr('id')) || in_array($id, $ids)) {
                if (null === $id) {
                    $id = '';
                    foreach ($entries as $entry) {
                        /** @var Entry $entry */
                        $entry = $entry['entry'];
                        $id .= $this->prepareId($entry->getTitle()) . '-';
                    }
                    $id .= $this->prepareId($header->text());
                }

                // Find new id
                $idPattern = $id;
                $i = 1;
                while ($query->find(sprintf('[id="%s"]', $id))->count() > 0) {
                    $id = sprintf('%s-%d', $idPattern, $i);
                    $i++;
                }

                // Set new id to header
                $header->attr('id', $id);
            }

            // Create summary entry
            $entry = new Entry($header->text(), $page->getPath());
            $entry->setId($id);

            // Add entry to summary hierarchy
            if (($lastEntry = end($entries)) !== false) {
                /** @var Entry $lastEntry */
                $lastEntry = $lastEntry['entry'];
                $lastEntry->addEntry($entry);
            }

            $entries[] = ['entry' => $entry, 'level' => $headerLevel];

            if (count($entries) <= 1) {
                $summary->addEntry($entry);
            }
        }

        $page->setContents($query->find('html > body')->html());

        $summary->orderEntries();
    }

    /**
     * Prepare ID.
     *
     * @param string $str
     *
     * @return string
     */
    private function prepareId(string $str): string
    {
        $id = preg_replace(['/[^\w\s\-]/i', '/\s+/', '/-{2,}/'], ['', '-', '-'], $str);
        $id = trim(mb_strtolower($id), '-');

        return $id;
    }

    /**
     * Get header level.
     *
     * @param Query $element
     *
     * @return int
     * @throws QueryException
     * @throws SelectorException
     */
    private function getHeaderLevel(Query $element): int
    {
        if ($element->is('h3')) {
            return 2;
        }
        if ($element->is('h4')) {
            return 3;
        }
        if ($element->is('h5')) {
            return 4;
        }
        if ($element->is('h6')) {
            return 5;
        }

        return 1;
    }
}