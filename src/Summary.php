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

namespace Berlioz\DocParser;

use Berlioz\DocParser\File\Page;
use Berlioz\DocParser\Summary\Entry;
use Berlioz\DocParser\Summary\EntryIterable;
use Berlioz\DocParser\Summary\EntryIterableInterface;

class Summary implements EntryIterableInterface
{
    use EntryIterable;
    /** @var int Max visibility level */
    private $maxVisibilityLevel;

    /**
     * Summary constructor.
     *
     * @param int|null $maxVisibilityLevel
     */
    public function __construct(?int $maxVisibilityLevel = null)
    {
        $this->maxVisibilityLevel = $maxVisibilityLevel;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['maxVisibilityLevel' => $this->maxVisibilityLevel,
                          'entries'            => $this->entries]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->maxVisibilityLevel = $unserialized['maxVisibilityLevel'];
        $this->entries = $unserialized['entries'];
    }

    /**
     * Filter titles.
     *
     * @param string[] $titles
     *
     * @return string[]
     */
    protected function filterTitles(array $titles): array
    {
        $titles = array_map('trim', $titles);
        $titles = array_filter($titles);

        return $titles;
    }

    /**
     * Add page to summary.
     *
     * @param \Berlioz\DocParser\File\Page $page
     *
     * @return $this
     */
    public function addPage(Page $page)
    {
        $visible = !($page->getMeta('index-visible') === false);

        if (!empty($titles = $page->getMeta('index'))) {
            $titles = explode(';', $titles);
            $titles = $this->filterTitles($titles);
            $nbTitles = count($titles);
            $parentEntry = $this;

            for ($i = 0; $i < $nbTitles; $i++) {
                $entry = $parentEntry->getEntryByTitle($titles[$i]);

                // Create if not exists or it's the last of list
                if (is_null($entry)) {
                    $entry = new Entry();
                    $entry->setTitle($titles[$i]);
                    $parentEntry->addEntry($entry);
                }

                // Define url and order if it's last element
                if ($i + 1 == $nbTitles) {
                    $entryVisible = ($visible === true && (is_null($this->maxVisibilityLevel) || $this->maxVisibilityLevel >= ($i + 1)));
                    $entry->setUrl($page->getUrlPath())
                          ->setOrder($page->getMeta('index-order'))
                          ->setVisible($entryVisible, $entryVisible);
                }

                $parentEntry = $entry;
            }
        }

        return $this;
    }
}