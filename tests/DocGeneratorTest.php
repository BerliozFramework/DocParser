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
use Berlioz\DocParser\Parser\ParserInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class DocGeneratorTest extends TestCase
{
    public function testHandle()
    {
        $adapter = new LocalFilesystemAdapter(realpath(__DIR__ . '/_test'));
        $filesystem = new Filesystem($adapter);

        $generator = new DocGenerator();
        $generator->addParser(new Markdown());
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

    public function testParserAcceptFileFallsBackToMime()
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('acceptExtension')->willReturn(false);
        $parser->method('acceptMime')->willReturnCallback(
            fn(string $mime) => $mime === 'text/markdown'
        );

        $generator = new DocGenerator();
        $generator->addParser($parser);

        // Use reflection to test the private parserAcceptFile method
        $method = new \ReflectionMethod($generator, 'parserAcceptFile');
        $method->setAccessible(true);

        // File with no extension but with a matching MIME type
        $fileAttributes = new FileAttributes('readme', null, null, null, 'text/markdown');
        $this->assertTrue($method->invoke($generator, $parser, $fileAttributes));

        // File with no extension and non-matching MIME type
        $fileAttributes = new FileAttributes('readme', null, null, null, 'text/plain');
        $this->assertFalse($method->invoke($generator, $parser, $fileAttributes));

        // File with no extension and no MIME type
        $fileAttributes = new FileAttributes('readme');
        $this->assertFalse($method->invoke($generator, $parser, $fileAttributes));
    }
}
