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

namespace Berlioz\DocParser\Tests\Treatment;

use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Tests\TraitFakeDocumentation;
use Berlioz\HtmlSelector\HtmlSelector;
use PHPUnit\Framework\TestCase;

class PathTreatmentTest extends TestCase
{
    use TraitFakeDocumentation;

    public function testLinkToPageWithSpaceIsEncoded()
    {
        $documentation = $this->getFakeDocumentation();

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('with space/my page');
        $this->assertNotNull($page);

        $query = (new HtmlSelector())->query($page->getContents());

        // Link to a sibling page whose path contains a space must be encoded.
        $sibling = $query->find('a:contains(sibling)');
        $this->assertCount(1, $sibling);
        $this->assertEquals('./other%20page', $sibling->attr('href'));

        // Link to the home page (no space) stays unchanged.
        $home = $query->find('a:contains(home page)');
        $this->assertCount(1, $home);
        $this->assertEquals('../index', $home->attr('href'));
    }

    public function testEncodedLinkResolvesToPage()
    {
        $documentation = $this->getFakeDocumentation();

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('with space/my page');
        $this->assertNotNull($page);

        $query = (new HtmlSelector())->query($page->getContents());
        $href = $query->find('a:contains(sibling)')->attr('href');
        $this->assertNotNull($href);

        // The encoded href, once resolved against the current page, must
        // point back to an existing page in the documentation.
        $resolved = ltrim(b_resolve_absolute_path('/' . $page->getPath(), $href), '/');
        $this->assertNotNull($documentation->handle($resolved));
    }
}
