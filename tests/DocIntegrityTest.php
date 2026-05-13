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
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\DocIntegrity;
use PHPUnit\Framework\TestCase;

class DocIntegrityTest extends TestCase
{
    use TraitFakeDocumentation;

    public function testCheck()
    {
        $docIntegrity = new DocIntegrity();
        $result = $docIntegrity->check($this->getFakeDocumentation());

        $this->assertCount(2, $result);
        $this->assertEquals('hospes/magnis', $result[0]['page']->getPath());
        $this->assertEquals('link', $result[0]['type']);
        $this->assertEquals('./usum.md', $result[0]['path']);

        $this->assertEquals('hospes/magnis', $result[1]['page']->getPath());
        $this->assertEquals('media', $result[1]['type']);
        $this->assertEquals('../assets/magnis.jpg', $result[1]['path']);
    }

    public function testCheckCollectsErrorsFromMultiplePages()
    {
        $documentation = new Documentation('test');

        // Page 1 with a broken link
        $stream1 = fopen('php://memory', 'r+');
        fwrite($stream1, '<a href="missing-page.md">Link</a>');
        rewind($stream1);
        $page1 = new Page($stream1, 'page1.md', 'text/html');

        // Page 2 with a broken link
        $stream2 = fopen('php://memory', 'r+');
        fwrite($stream2, '<a href="other-missing.md">Link</a>');
        rewind($stream2);
        $page2 = new Page($stream2, 'page2.md', 'text/html');

        $documentation->getFiles()->addFile($page1);
        $documentation->getFiles()->addFile($page2);

        $docIntegrity = new DocIntegrity();
        $result = $docIntegrity->check($documentation);

        // Both pages have errors; array_push (not +=) must collect all of them
        $this->assertCount(2, $result);
        $pages = array_map(fn($e) => $e['page']->getFilename(), $result);
        $this->assertContains('page1.md', $pages);
        $this->assertContains('page2.md', $pages);
    }
}
