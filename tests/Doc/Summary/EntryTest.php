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

namespace Berlioz\DocParser\Tests\Doc\Summary;

use Berlioz\DocParser\Doc\Summary\Entry;
use PHPUnit\Framework\TestCase;

class EntryTest extends TestCase
{
    public function testParent()
    {
        $entry = new Entry('Entry');
        $subEntry = new Entry('Sub entry');

        $this->assertNull($subEntry->getParent());

        $subEntry->setParent($entry);
        $this->assertSame($entry, $subEntry->getParent());
    }

    public function testActive()
    {
        $entry = new Entry('Entry');
        $entry->addEntry($subEntry = new Entry('Sub entry'));

        $this->assertFalse($entry->isActive());
        $this->assertFalse($subEntry->isActive());

        $entry->setActive(true);
        $this->assertTrue($entry->isActive());
        $this->assertFalse($subEntry->isActive());

        $subEntry->setActive(false, true);
        $this->assertFalse($entry->isActive());
        $this->assertFalse($subEntry->isActive());

        $subEntry->setActive(true, true);
        $this->assertTrue($entry->isActive());
        $this->assertTrue($subEntry->isActive());
    }

    public function testVisible()
    {
        $entry = new Entry('Entry');
        $entry->addEntry($subEntry = new Entry('Sub entry'));

        $this->assertTrue($entry->isVisible());
        $this->assertTrue($subEntry->isVisible());

        $entry->setVisible(false);
        $this->assertFalse($entry->isVisible());
        $this->assertTrue($subEntry->isVisible());

        $subEntry->setVisible(true, true);
        $this->assertTrue($entry->isVisible());
        $this->assertTrue($subEntry->isVisible());

        $subEntry->setVisible(false, true);
        $this->assertFalse($entry->isVisible());
        $this->assertFalse($subEntry->isVisible());
    }

    public function testOrder()
    {
        $mainEntry = new Entry('Main entry');
        $mainEntry
            ->addEntry($entry1 = new Entry('Entry 1'))
            ->addEntry($entry2 = new Entry('Entry 2'))
            ->addEntry($entry3 = new Entry('Entry 3'));

        $this->assertCount(3, $mainEntry);

        $entry2->setOrder(1);
        $mainEntry->orderEntries();
        $entries = $mainEntry->getEntries();

        $this->assertSame($entry2, reset($entries));
        $this->assertSame($entry1, next($entries));
        $this->assertSame($entry3, next($entries));

        $entry3->setOrder(0);
        $mainEntry->orderEntries();
        $entries = $mainEntry->getEntries();

        $this->assertSame($entry3, reset($entries));
        $this->assertSame($entry2, next($entries));
        $this->assertSame($entry1, next($entries));
    }

    public function testCountVisible()
    {
        $mainEntry = new Entry('Main entry');
        $mainEntry
            ->addEntry($entry1 = new Entry('Entry 1'))
            ->addEntry($entry2 = new Entry('Entry 2'))
            ->addEntry($entry3 = new Entry('Entry 3'));

        $this->assertEquals(3, $mainEntry->countVisible());
        $this->assertEquals(0, $mainEntry->countVisible(false));

        $entry2->setVisible(false);

        $this->assertEquals(2, $mainEntry->countVisible());
        $this->assertEquals(1, $mainEntry->countVisible(false));
    }

    public function testTitle()
    {
        $mainEntry = new Entry($title = 'Main entry');

        $this->assertEquals($title, $mainEntry->getTitle());

        $mainEntry->setTitle($title = 'New title');
        $this->assertEquals($title, $mainEntry->getTitle());
    }

    public function testId()
    {
        $mainEntry = new Entry('Main entry');

        $this->assertNull($mainEntry->getId());

        $mainEntry->setId($id = 'my-id');
        $this->assertEquals($id, $mainEntry->getId());
    }

    public function testPath()
    {
        $mainEntry = new Entry('Main entry');

        $this->assertNull($mainEntry->getPath());

        $mainEntry->setPath($path = '/path/to');
        $this->assertEquals($path, $mainEntry->getPath());

        $mainEntry = new Entry('Main entry', $path = '/path/to');
        $this->assertEquals($path, $mainEntry->getPath());
    }

    public function testSerialization()
    {
        $mainEntry = new Entry('Main entry', '/path/to');
        $mainEntry
            ->setId('my-id')
            ->setOrder(2)
            ->setVisible(true)
            ->setActive(false);

        $mainEntry2 = unserialize(serialize($mainEntry));

        $this->assertEquals($mainEntry2, $mainEntry);
    }
}
