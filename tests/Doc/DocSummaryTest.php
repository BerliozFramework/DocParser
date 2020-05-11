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

use Berlioz\DocParser\Doc\DocSummary;
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Doc\Summary\Entry;
use PHPUnit\Framework\TestCase;

class DocSummaryTest extends TestCase
{
    public function testAddPage()
    {
        $page = new Page(fopen('php://memory', 'r'), 'test.md');
        $page->setTitle('My page');

        $page2 = new Page(fopen('php://memory', 'r'), 'test2.md');
        $page2->setTitle('My page 2');

        $docSummary = new DocSummary();
        $docSummary->addPage($page);
        $docSummary->addPage($page2);

        // No breadcrumb
        $this->assertCount(0, $docSummary);

        $page->setMetas(['breadcrumb' => 'My; Breadcrumb']);
        $page2->setMetas(['breadcrumb' => 'My']);
        $docSummary->addPage($page);
        $docSummary->addPage($page2);

        $this->assertCount(1, $docSummary);
        $this->equalTo(2, $docSummary->countRecursive());

        $entries = $docSummary->getEntries();
        $firstEntry = reset($entries);
        $subEntries = $firstEntry->getEntries();
        $subEntry = reset($subEntries);
        $this->assertEquals('My', $firstEntry->getTitle());
        $this->assertEquals('Breadcrumb', $subEntry->getTitle());
    }

    public function testFindByPage()
    {
        $page = new Page(fopen('php://memory', 'r'), 'test.md');
        $page->setTitle('My page')->setMetas(['breadcrumb' => 'My; Breadcrumb']);

        $page2 = new Page(fopen('php://memory', 'r'), 'test2.md');
        $page2->setTitle('My page 2')->setMetas(['breadcrumb' => 'My']);

        $page3 = new Page(fopen('php://memory', 'r'), 'test3.md');
        $page3->setTitle('My page 3');

        $docSummary = new DocSummary();
        $docSummary->addPage($page);
        $docSummary->addPage($page2);
        $docSummary->addPage($page3);

        $this->assertInstanceOf(Entry::class, $found = $docSummary->findByPage($page2));
        $this->assertEquals('My', $found->getTitle());
        $this->assertNull($docSummary->findByPage($page3));
    }
}
