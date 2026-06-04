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
use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\FileSet;
use Berlioz\DocParser\Tests\TraitFakeDocumentation;
use Berlioz\HtmlSelector\HtmlSelector;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DocumentationTest extends TestCase
{
    use TraitFakeDocumentation;

    public function testSerialization()
    {
        $doc = $this->getFakeDocumentation();
        /** @var Documentation $doc2 */
        $doc2 = unserialize(serialize($doc));

        $this->assertInstanceOf(Documentation::class, $doc2);

        /** @var FileInterface $file */
        foreach ($doc->getFiles() as $file) {
            $this->assertNotNull($doc2->getFiles()->findByFilename($file->getFilename()));
        }
    }

    public function testUnserializeLegacyDataWithoutDate()
    {
        $doc = new Documentation('v1.x');
        $doc->__unserialize([
            'version' => 'v1.x',
            'summary' => $doc->getSummary(),
            'files' => $doc->getFiles(),
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $doc->getDate());
    }

    public function testGetDateDefault()
    {
        $before = new DateTimeImmutable();
        $doc = new Documentation('v1.x');
        $after = new DateTimeImmutable();

        $this->assertInstanceOf(DateTimeImmutable::class, $doc->getDate());
        $this->assertGreaterThanOrEqual($before, $doc->getDate());
        $this->assertLessThanOrEqual($after, $doc->getDate());
    }

    public function testGetDateInjected()
    {
        $date = new DateTimeImmutable('2020-01-01 12:00:00');
        $doc = new Documentation('v1.x', null, $date);

        $this->assertEquals($date, $doc->getDate());
    }

    public function testGetDateWithInjectedFiles()
    {
        $files = new FileSet();
        $date = new DateTimeImmutable('2020-01-01 12:00:00');
        $doc = new Documentation('v1.x', $files, $date);

        $this->assertSame($files, $doc->getFiles());
        $this->assertEquals($date, $doc->getDate());
    }

    public function testGetVersion()
    {
        $doc = new Documentation('v1.x');

        $this->assertEquals('v1.x', $doc->getVersion());
    }

    public function testGetSummary()
    {
        $doc = new Documentation('v1.x');
        $this->assertInstanceOf(DocSummary::class, $doc->getSummary());
        $this->assertEquals(0, $doc->getSummary()->countRecursive());

        $doc = $this->getFakeDocumentation();
        $this->assertEquals(3, $doc->getSummary()->countRecursive());
    }

    public function testGetFiles()
    {
        $doc = new Documentation('v1.x');

        $this->assertInstanceOf(FileSet::class, $doc->getFiles());
        $this->assertCount(0, $doc->getFiles());

        $doc = $this->getFakeDocumentation();

        $this->assertInstanceOf(FileSet::class, $doc->getFiles());
        $this->assertCount(13, $doc->getFiles());

        foreach ($doc->getFiles() as $file) {
            $this->assertInstanceOf(FileInterface::class, $file);
        }
    }

    public function testPathWithAnchor()
    {
        $doc = $this->getFakeDocumentation();
        $page = $doc->handle('/hospes/magnis');

        $this->assertNotNull($page);

        $query = (new HtmlSelector())->query($page->getContents())->find('a:contains(currere contemptrix)');
        $this->assertCount(1, $query);
        $this->assertEquals('../index#o-infecta-ossibus-ripa', $query->attr('href'));
    }

    public function testHandleWithSpaceInPathDecoded()
    {
        $doc = $this->getFakeDocumentation();

        $page = $doc->handle('with space/my page');

        $this->assertNotNull($page);
        $this->assertEquals('with space/my page', $page->getPath());
    }

    public function testHandleWithSpaceInPathEncoded()
    {
        $doc = $this->getFakeDocumentation();

        $page = $doc->handle('with%20space/my%20page');

        $this->assertNotNull($page);
        $this->assertEquals('with space/my page', $page->getPath());
    }
}
