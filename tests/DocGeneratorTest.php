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

    public function testGetConfigDoesNotMutateInternalConfig()
    {
        $generator = new DocGenerator(['existing' => 'value']);

        // Calling getConfig with a default should not store the default
        $result = $generator->getConfig('non-existent', 'default-value');
        $this->assertEquals('default-value', $result);

        // Calling again without default should return null, not the previously passed default
        $result = $generator->getConfig('non-existent');
        $this->assertNull($result);

        // Existing keys should still work
        $this->assertEquals('value', $generator->getConfig('existing'));
    }

    public function testParsersRespectPriorityOrder()
    {
        // Parser A: low priority (200), accepts .md
        $parserA = $this->createMock(ParserInterface::class);
        $parserA->method('acceptExtension')->willReturnCallback(fn(string $ext) => $ext === 'md');
        $parserA->method('acceptMime')->willReturn(false);
        $parserA->method('parse')->willReturn(
            new Page(fopen('php://memory', 'r+'), 'from-parser-a.md', 'text/html')
        );

        // Parser B: high priority (10), accepts .md, returns a different page
        $parserB = $this->createMock(ParserInterface::class);
        $parserB->method('acceptExtension')->willReturnCallback(fn(string $ext) => $ext === 'md');
        $parserB->method('acceptMime')->willReturn(false);
        $parserB->method('parse')->willReturn(
            new Page(fopen('php://memory', 'r+'), 'from-parser-b.md', 'text/html')
        );

        // Add low priority first, then high priority — high should still win
        $generator = new DocGenerator();
        $generator->addParser($parserA, 200);
        $generator->addParser($parserB, 10);

        $adapter = new LocalFilesystemAdapter(realpath(__DIR__ . '/_test'));
        $filesystem = new Filesystem($adapter);
        $documentation = $generator->handle('test', $filesystem);

        // Parser B (priority 10) should be used; all .md pages should have filename 'from-parser-b.md'
        $pages = $documentation->getFiles()->getFiles(Page::class);
        $this->assertNotEmpty($pages);
        foreach ($pages as $page) {
            $this->assertEquals('from-parser-b.md', $page->getFilename());
        }
    }
}
