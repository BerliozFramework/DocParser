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

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\RawFile;
use Berlioz\DocParser\DocCacheGenerator;
use DateTimeImmutable;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Throwable;

class DocCacheGeneratorTest extends TestCase
{
    use TraitFakeDocumentation;

    public function testFile()
    {
        $file = new RawFile(
            fopen($filename = __DIR__ . '/_test/assets/anomaly.jpg', 'r'),
            'assets/anomaly.jpg',
            'image/jpg',
            (new DateTimeImmutable())->setTimestamp(filemtime($filename))
        );
        $expectedContents = $file->getContents();

        $documentation = new Documentation('1.0');
        $documentation->getFiles()->addFile($file);

        $adapter = new InMemoryFilesystemAdapter();
        $filesystem = new Filesystem($adapter);
        $docCacheGenerator = new DocCacheGenerator($filesystem);

        // Save documentation (writes the file stream to cache)
        $docCacheGenerator->save($documentation);
        $this->assertTrue($filesystem->fileExists($docCacheGenerator->getFileCacheName($file)));

        // Restore documentation and read back the file contents
        $documentationFromCache = $docCacheGenerator->get($documentation->getVersion());
        $fileFromCache = $documentationFromCache->getFiles()->findByFilename('assets/anomaly.jpg');

        $this->assertNotNull($fileFromCache);
        $this->assertEquals($expectedContents, $fileFromCache->getContents());
    }

    public function testDocumentation()
    {
        $documentation = $this->getFakeDocumentation();

        $adapter = new InMemoryFilesystemAdapter();
        $filesystem = new Filesystem($adapter);
        $docCacheGenerator = new DocCacheGenerator($filesystem);

        // Save documentation
        $docCacheGenerator->save($documentation);

        // Restore documentation
        $documentationFromCache = $docCacheGenerator->get($documentation->getVersion());

        $this->assertInstanceOf(Documentation::class, $documentationFromCache);
        $this->assertEquals($documentation->getVersion(), $documentationFromCache->getVersion());
        $this->assertEquals($documentation->getSummary(), $documentationFromCache->getSummary());
        $this->assertEquals($documentation->getFiles()->count(), $documentationFromCache->getFiles()->count());

        /** @var FileInterface $file */
        foreach ($documentation->getFiles() as $file) {
            $this->assertNotNull($documentationFromCache->getFiles()->findByFilename($file->getFilename()));
        }
    }

    public function testPrefix()
    {
        $file = new RawFile(
            fopen($filename = __DIR__ . '/_test/assets/anomaly.jpg', 'r'),
            'assets/anomaly.jpg',
            'image/jpg',
            (new DateTimeImmutable())->setTimestamp(filemtime($filename))
        );

        $documentation = new Documentation('1.0');
        $documentation->getFiles()->addFile($file);

        $adapter = new InMemoryFilesystemAdapter();
        $filesystem = new Filesystem($adapter);
        $docCacheGenerator = new DocCacheGenerator($filesystem, '/my/prefix');

        $docCacheGenerator->save($documentation);
        $fileCacheName = $docCacheGenerator->getFileCacheName($file);

        $this->assertEquals('/my/prefix/4b/4b132a542f71168cae423e7f39fe119f', $fileCacheName);
        $this->assertTrue($filesystem->fileExists($fileCacheName));
    }

    public function testGetCorruptedCacheCallsErrorHandler()
    {
        $adapter = new InMemoryFilesystemAdapter();
        $filesystem = new Filesystem($adapter);

        // Write corrupted data to cache
        $version = 'corrupted';
        $cacheGenerator = new DocCacheGenerator($filesystem);
        $cacheName = $cacheGenerator->getDocCacheName($version);
        $filesystem->write($cacheName, 'not_a_valid_serialized_documentation');

        // Without error handler: returns null silently
        $result = $cacheGenerator->get($version);
        $this->assertNull($result);

        // With error handler: returns null but handler is called
        $caughtException = null;
        $cacheGeneratorWithHandler = new DocCacheGenerator(
            $filesystem,
            '/',
            [],
            function (Throwable $e) use (&$caughtException) {
                $caughtException = $e;
            }
        );
        $result = $cacheGeneratorWithHandler->get($version);

        $this->assertNull($result);
        $this->assertNotNull($caughtException);
    }
}
