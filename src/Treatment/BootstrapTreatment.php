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
use Berlioz\DocParser\DocGenerator;
use Berlioz\HtmlSelector\Exception\QueryException;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\Query;

/**
 * Class BootstrapTreatment.
 *
 * @package Berlioz\DocParser\Treatment
 */
class BootstrapTreatment implements TreatmentInterface
{
    private DocGenerator $docGenerator;

    /**
     * BootstrapTreatment constructor.
     *
     * @param DocGenerator $docGenerator
     */
    public function __construct(DocGenerator $docGenerator)
    {
        $this->docGenerator = $docGenerator;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     * @throws SelectorException
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
     * @throws QueryException
     * @throws SelectorException
     */
    public function doBootstrapTreatment(Page $page): void
    {
        $query = Query::loadHtml($page->getContents());

        // Images
        $query->find('img')->addClass('img-fluid');

        // Tables
        $query->find('table')->addClass('table table-striped');

        $page->setContents($query->find('html > body')->html());
    }
}