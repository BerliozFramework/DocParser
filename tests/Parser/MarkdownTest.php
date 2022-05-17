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
use Berlioz\DocParser\Parser\Markdown;
use DateTimeImmutable;
use League\Flysystem\FileAttributes;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{
    protected function getParser(): Markdown
    {
        return new Markdown();
    }

    public function testAcceptExtension()
    {
        $this->assertTrue($this->getParser()->acceptExtension('md'));
        $this->assertFalse($this->getParser()->acceptExtension('doc'));
    }

    public function testAcceptMime()
    {
        $this->assertTrue($this->getParser()->acceptMime('text/markdown'));
        $this->assertTrue($this->getParser()->acceptMime('text/x-markdown'));
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
                FileAttributes::ATTRIBUTE_PATH => $fileName = 'foo/bar/baz.md',
                FileAttributes::ATTRIBUTE_TYPE => FileAttributes::TYPE_FILE,
            ]
        );
        $page = $this->getParser()->parse(
            <<<EOF
---
title: Page title
slug: page
summary-visible: false
---

# Title H1

## Title H2

Content
EOF,
            $fileAttributes
        );

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($fileName, $page->getFilename());
        $this->assertEquals($fileMime, $page->getMime());
//        $this->assertEquals('foo/bar/page', $page->getPath());
        $this->assertEquals((new DateTimeImmutable())->setTimestamp($fileMTime), $page->getDatetime());
        $this->assertEquals(
            '<h1>Title H1</h1>' . "\n" .
            '<h2>Title H2</h2>' . "\n" .
            '<p>Content</p>' . "\n",
            $page->getContents()
        );
        $this->assertFalse($page->getMeta('summary-visible'));
    }
}
