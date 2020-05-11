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

namespace Berlioz\DocParser\Tests\Doc\File;

use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Doc\File\RawFile;
use Berlioz\DocParser\Doc\PageSummary;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    private function getPage(): Page
    {
        return new Page(
            fopen(
                __DIR__ .
                DIRECTORY_SEPARATOR .
                '..' .
                DIRECTORY_SEPARATOR .
                '..' .
                DIRECTORY_SEPARATOR .
                '_test' .
                DIRECTORY_SEPARATOR .
                'index.md',
                'r'
            ),
            '_test' . DIRECTORY_SEPARATOR . 'index.md',
            'text/markdown',
            new \DateTimeImmutable('2020-05-03 10:00:00')
        );
    }

    public function testSummary()
    {
        $page = $this->getPage();

        $this->assertInstanceOf(PageSummary::class, $page->getSummary());

        $newSummary = new PageSummary();
        $page->setSummary($newSummary);

        $this->assertSame($newSummary, $page->getSummary());
    }

    public function testSerialization()
    {
        $page = $this->getPage();
        /** @var RawFile $page2 */
        $page2 = unserialize(serialize($page));

        $this->assertInstanceOf(Page::class, $page2);
        $this->assertNull($page2->getStream());

        $page2->setStream($page->getStream());
        $this->assertEquals($page, $page2);
    }

    public function testGetPath()
    {
        $page = $this->getPage();

        $this->assertEquals('_test/index', $page->getPath());

        $page->setMetas(['slug' => 'foo']);
        $this->assertEquals('_test/foo', $page->getPath());

        $page->setMetas(['slug' => '/foo']);
        $this->assertEquals('_test/%2Ffoo', $page->getPath());
    }

    public function testMetas()
    {
        $page = $this->getPage();
        $page->setMetas($metas = ['title' => 'My title', 'slug' => '/foo']);

        $this->assertEquals($metas, $page->getMetas());
        $this->assertEquals('My title', $page->getMeta('title', 'Bar'));
        $this->assertEquals('/foo', $page->getMeta('slug'));
        $this->assertEquals('Bar', $page->getMeta('unknown', 'Bar'));
        $this->assertNull($page->getMeta('unknown'));
    }

    public function testGetTitle()
    {
        $page = $this->getPage();

        $this->assertNull($page->getTitle());
        $page->setMetas(['title' => 'My title']);
        $this->assertEquals('My title', $page->getTitle());

        $page->setTitle('Foo');
        $this->assertEquals('Foo', $page->getTitle());
    }
}
