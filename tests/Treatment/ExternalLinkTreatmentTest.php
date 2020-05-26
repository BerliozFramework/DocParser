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
use Berlioz\DocParser\Treatment\ExternalLinkTreatment;
use Berlioz\HtmlSelector\Query;

class ExternalLinkTreatmentTest extends AbstractTestCase
{
    public function testDoExternalLinksTreatment()
    {
        $externalLinksTreatment = new ExternalLinkTreatment($this->getDocGenerator());
        $documentation = $this->getDocumentation();
        $this->getDocGenerator()->setConfig(['treatment.external-links.target' => '_self']);

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('images');

        $externalLinksTreatment->doExternalLinksTreatment($page);

        $queryTest = Query::loadHtml($page->getContents());

        foreach ($queryTest->find('a[href]') as $link) {
            if ($externalLinksTreatment->isExternalLink($link->attr('href'))) {
                $this->assertEquals('nofollow noopener', $link->attr('rel'));
                $this->assertEquals('_self', $link->attr('target'));
                continue;
            }

            $this->assertNull($link->attr('rel'));
            $this->assertNull($link->attr('target'));
        }
    }

    public function testIsExternalLink()
    {
        $externalLinksTreatment = new ExternalLinkTreatment($this->getDocGenerator());
        $this->getDocGenerator()->setConfig(['treatment.external-links.hosts' => ['getberlioz.com', '.getberlioz.local']]);

        $this->assertFalse($externalLinksTreatment->isExternalLink('https://getberlioz.com'));
        $this->assertFalse($externalLinksTreatment->isExternalLink('http://getberlioz.com'));
        $this->assertFalse($externalLinksTreatment->isExternalLink('//getberlioz.local'));
        $this->assertFalse($externalLinksTreatment->isExternalLink('//www.getberlioz.local'));
        $this->assertFalse($externalLinksTreatment->isExternalLink('./page'));
        $this->assertFalse($externalLinksTreatment->isExternalLink('/page'));
        $this->assertFalse($externalLinksTreatment->isExternalLink(':80/test'));
        $this->assertTrue($externalLinksTreatment->isExternalLink('//www.getberlioz.com'));
        $this->assertTrue($externalLinksTreatment->isExternalLink('//google.fr'));
    }
}
