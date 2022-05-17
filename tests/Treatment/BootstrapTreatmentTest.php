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
use Berlioz\DocParser\Treatment\BootstrapTreatment;
use Berlioz\HtmlSelector\HtmlSelector;

class BootstrapTreatmentTest extends AbstractTestCase
{
    public function testDoBootstrapTreatment()
    {
        $bootstrapTreatment = new BootstrapTreatment();
        $documentation = $this->getDocumentation();

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('images');
        $this->assertNotNull($page);
        $bootstrapTreatment->doBootstrapTreatment($page);

        $html = (new HtmlSelector())->query($page->getContents());

        // Images
        $this->assertCount(1, $html->find('img'));
        $this->assertTrue($html->find('img')->hasClass('img-fluid'));

        // Tables
        $this->assertCount(1, $html->find('table'));
        $this->assertTrue($html->find('table')->hasClass('table'));
        $this->assertTrue($html->find('table')->hasClass('table-striped'));
    }
}
