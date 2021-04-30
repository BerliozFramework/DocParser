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

namespace Berlioz\DocParser\Tests\Parser;

use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Parser\reStructuredText;
use DateTimeImmutable;
use League\Flysystem\FileAttributes;
use PHPUnit\Framework\TestCase;

class reStructuredTextTest extends TestCase
{
    protected function getParser(): reStructuredText
    {
        return new reStructuredText();
    }

    public function testAcceptExtension()
    {
        $this->assertTrue($this->getParser()->acceptExtension('rst'));
        $this->assertFalse($this->getParser()->acceptExtension('doc'));
    }

    public function testAcceptMime()
    {
        $this->assertTrue($this->getParser()->acceptMime('text/rst'));
        $this->assertTrue($this->getParser()->acceptMime('text/x-rst'));
        $this->assertFalse($this->getParser()->acceptMime('text/html'));
    }

    public function testParse()
    {
        /** @var FileAttributes $fileAttributes */
        $fileAttributes = FileAttributes::fromArray(
            [
                FileAttributes::ATTRIBUTE_FILE_SIZE => $fileSize = rand(12345, 123456),
                FileAttributes::ATTRIBUTE_LAST_MODIFIED => $fileMTime = time(),
                FileAttributes::ATTRIBUTE_MIME_TYPE => $fileMime = 'text/markdown',
                FileAttributes::ATTRIBUTE_PATH => $fileName = 'foo/bar/baz.rst',
                FileAttributes::ATTRIBUTE_TYPE => FileAttributes::TYPE_FILE,
            ]
        );
        $page = $this->getParser()->parse(
            <<<EOF
Title H1
========

Title H2
--------

Content

.. index::
    :title: Page title
    :slug: page
    :summary-visible: false
EOF,
            $fileAttributes
        );

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($fileName, $page->getFilename());
        $this->assertEquals($fileMime, $page->getMime());
        $this->assertEquals('foo/bar/page', $page->getPath());
        $this->assertEquals((new DateTimeImmutable())->setTimestamp($fileMTime), $page->getDatetime());
        $this->assertEquals(
            '<a id="title.1"></a><h1>Title H1</h1>' . "\n" .
            '<a id="title.1.1"></a><h2>Title H2</h2>' . "\n" .
            '<p>Content</p>' . "\n\n",
            $page->getContents()
        );
        $this->assertFalse($page->getMeta('summary-visible'));
    }
}
