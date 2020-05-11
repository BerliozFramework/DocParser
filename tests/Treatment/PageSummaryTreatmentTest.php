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
use Berlioz\DocParser\Treatment\PageSummaryTreatment;

class PageSummaryTreatmentTest extends AbstractTestCase
{
    public function testMakePageSummary()
    {
        $pageSummaryTreatment = new PageSummaryTreatment();
        $documentation = $this->getDocumentation();

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('tamen');

        $this->assertNotNull($page);
        $this->assertEquals(0, $page->getSummary()->countRecursive());

        $pageSummaryTreatment->makePageSummary($page);

        $this->assertEquals(5, $page->getSummary()->countRecursive());
    }
}
