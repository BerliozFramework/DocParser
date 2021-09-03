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
use Berlioz\DocParser\Treatment\TitleTreatment;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\Query;

class TitleTreatmentTest extends AbstractTestCase
{
    public function testDoTitleTreatment()
    {
        $titleTreatment = new TitleTreatment($this->getDocGenerator());
        $documentation = $this->getDocumentation();

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('tamen');
        $this->assertNotNull($page);
        $titleTreatment->doTitleTreatment($page);

        $this->assertEquals('Tamen vertice audet tum', $page->getTitle());
        $this->assertCount(1, (new HtmlSelector())->query($page->getContents())->find('h1'));

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('images');
        $this->assertNotNull($page);
        $titleTreatment->doTitleTreatment($page);

        $this->assertEquals('Title of page', $page->getTitle());
        $this->assertCount(1, (new HtmlSelector())->query($page->getContents())->find('h1'));

        /// Remove H1 option

        $this->getDocGenerator()->setConfig(['treatment.remove-h1' => true]);

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('in_dea');
        $this->assertNotNull($page);
        $titleTreatment->doTitleTreatment($page);

        $this->assertEquals('In dea mali modo Priamus corpore', $page->getTitle());
        $this->assertCount(0, (new HtmlSelector())->query($page->getContents())->find('h1'));
    }
}
