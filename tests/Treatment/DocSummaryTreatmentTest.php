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

use Berlioz\DocParser\Treatment\DocSummaryTreatment;

class DocSummaryTreatmentTest extends AbstractTestCase
{
    public function testHandle()
    {
        $docSummaryTreatment = new DocSummaryTreatment();
        $documentation = $this->getDocumentation();

        $this->assertEquals(0, $documentation->getSummary()->countRecursive());

        $docSummaryTreatment->handle($documentation);

        $this->assertEquals(3, $documentation->getSummary()->countRecursive());
    }
}
