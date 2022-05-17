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

declare(strict_types=1);

namespace Berlioz\DocParser\Treatment;

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\HtmlSelector\Exception\HtmlSelectorException;
use Berlioz\HtmlSelector\HtmlSelector;

class BootstrapTreatment implements TreatmentInterface
{
    private HtmlSelector $htmlSelector;

    /**
     * BootstrapTreatment constructor.
     */
    public function __construct()
    {
        $this->htmlSelector = new HtmlSelector();
    }

    /**
     * @inheritDoc
     * @throws HtmlSelectorException
     */
    public function handle(Documentation $documentation): void
    {
        /** @var Page $page */
        foreach ($documentation->getFiles(fn($file) => $file instanceof Page) as $page) {
            $this->doBootstrapTreatment($page);
        }
    }

    /**
     * Do Bootstrap treatment.
     *
     * @param Page $page
     *
     * @throws HtmlSelectorException
     */
    public function doBootstrapTreatment(Page $page): void
    {
        $query = $this->htmlSelector->query($page->getContents());

        // Images
        $query->find('img')->addClass('img-fluid');

        // Tables
        $query->find('table')->addClass('table table-striped');

        $page->setContents($query->find('html > body')->html());
    }
}