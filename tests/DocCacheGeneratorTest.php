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

        $adapter = new InMemoryFilesystemAdapter();
        $filesystem = new Filesystem($adapter);
        $docCacheGenerator = new DocCacheGenerator($filesystem);

        $docCacheGenerator->saveFile($file);
        $fileCacheName = $docCacheGenerator->getFileCacheName($file);

        $this->assertTrue($filesystem->fileExists($fileCacheName));

        $fileFromCache = clone $file;
        $docCacheGenerator->readFile($file);

        $this->assertEquals($file->getContents(), $fileFromCache->getContents());
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
}
