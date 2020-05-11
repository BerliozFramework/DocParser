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
        $this->assertCount(10, $doc->getFiles());

        foreach ($doc->getFiles() as $file) {
            $this->assertInstanceOf(FileInterface::class, $file);
        }
    }
}
