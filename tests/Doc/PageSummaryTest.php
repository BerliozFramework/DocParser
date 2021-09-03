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

namespace Berlioz\DocParser\Tests\Doc;

use Berlioz\DocParser\Doc\PageSummary;
use Berlioz\DocParser\Doc\Summary\Entry;
use PHPUnit\Framework\TestCase;

class PageSummaryTest extends TestCase
{
    public function testSerialization()
    {
        $pageSummary = new PageSummary();
        $pageSummary->addEntry(new Entry('Entry 1'));
        $pageSummary->addEntry(new Entry('Entry 2'));

        $entry = new Entry('Entry 3');
        $entry->addEntry(new Entry('Entry 4'));
        $pageSummary->addEntry($entry);

        $pageSummary2 = unserialize(serialize($pageSummary));

        $this->assertEquals($pageSummary, $pageSummary2);
    }
}
