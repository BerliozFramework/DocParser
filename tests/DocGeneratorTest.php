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

namespace Berlioz\DocParser\Tests;

use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Doc\File\RawFile;
use Berlioz\DocParser\DocGenerator;
use Berlioz\DocParser\Parser\Markdown;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class DocGeneratorTest extends TestCase
{
    public function testHandle()
    {
        $adapter = new LocalFilesystemAdapter(realpath(__DIR__ . '/_test'));
        $filesystem = new Filesystem($adapter);

        $generator = new DocGenerator(new Markdown());
        $documentation = $generator->handle($docVersion = 'current', $filesystem);

        $this->assertEquals($docVersion, $documentation->getVersion());
        $this->assertCount(11, $documentation->getFiles());
        $this->assertEquals(3, $documentation->getSummary()->countRecursive());
        $this->assertNull($documentation->handle('imago.md'));
        $this->assertNotNull($page = $documentation->handle('images'));
        $this->assertInstanceOf(Page::class, $page);
        $this->assertInstanceOf(RawFile::class, $rawFile = $documentation->handle('assets/anomaly.jpg'));
        $this->assertNotInstanceOf(Page::class, $rawFile);
    }
}
