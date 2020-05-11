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

use Berlioz\DocParser\Doc\File\RawFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RawFileTest extends TestCase
{
    private function getRawFile(): RawFile
    {
        return new RawFile(
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

    public function testGetFilename()
    {
        $rawFile = $this->getRawFile();
        $this->assertEquals('_test' . DIRECTORY_SEPARATOR . 'index.md', $rawFile->getFilename());
    }

    public function testGetPath()
    {
        $rawFile = $this->getRawFile();
        $this->assertEquals('_test/index.md', $rawFile->getPath());
    }

    public function testStream()
    {
        $rawFile = $this->getRawFile();
        $this->assertIsResource($rawFile->getStream());

        $newResource = fopen(__DIR__ . '/../../_test/imago.md', 'r');
        $rawFile->setStream($newResource);

        $this->assertIsResource($rawFile->getStream());
        $this->assertSame($newResource, $rawFile->getStream());
    }

    public function testContents()
    {
        $rawFile = $this->getRawFile();

        $this->assertEquals(stream_get_contents($rawFile->getStream(), -1, 0), $rawFile->getContents());
        $this->assertEquals(stream_get_contents($rawFile->getStream(), -1, 0), (string)$rawFile);
    }

    public function testGetHash()
    {
        $rawFile = $this->getRawFile();

        $this->assertEquals(md5($rawFile->getFilename()), $rawFile->getHash());
    }

    public function testGetDatetime()
    {
        $rawFile = $this->getRawFile();

        $this->assertInstanceOf(\DateTimeInterface::class, $rawFile->getDatetime());
        $this->assertEquals('2020-05-03T10:00:00+00:00', $rawFile->getDatetime()->format(DATE_ATOM));
    }

    public function testSerialization()
    {
        $rawFile = $this->getRawFile();
        /** @var RawFile $rawFile2 */
        $rawFile2 = unserialize(serialize($rawFile));

        $this->assertInstanceOf(RawFile::class, $rawFile2);
        $this->assertNull($rawFile2->getStream());

        $rawFile2->setStream($rawFile->getStream());
        $this->assertEquals($rawFile, $rawFile2);
    }

    public function testGetMime()
    {
        $rawFile = $this->getRawFile();

        $this->assertEquals('text/markdown', $rawFile->getMime());
    }

    public function testResponse()
    {
        $rawFile = $this->getRawFile();

        $this->assertInstanceOf(ResponseInterface::class, $response = $rawFile->response());
        $this->assertSame($rawFile->getContents(), $response->getBody()->getContents());
        $this->assertSame($rawFile->getStream(), $response->getBody()->detach());
    }
}
