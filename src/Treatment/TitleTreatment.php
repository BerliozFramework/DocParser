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
use Berlioz\HtmlSelector\Exception\HtmlSelectorException;
use Berlioz\HtmlSelector\HtmlSelector;

class TitleTreatment implements TreatmentInterface
{
    private HtmlSelector $htmlSelector;

    /**
     * TitleTreatment constructor.
     *
     * @param DocGenerator $docGenerator
     */
    public function __construct(private DocGenerator $docGenerator)
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
            $this->doTitleTreatment($page);
        }
    }

    /**
     * Do title treatment.
     *
     * @param Page $page
     *
     * @throws HtmlSelectorException
     */
    public function doTitleTreatment(Page $page): void
    {
        $query = $this->htmlSelector->query($page->getContents());
        $h1 = $query->find('h1:first');

        if (0 === count($h1)) {
            return;
        }

        if (null === $page->getTitle()) {
            $page->setTitle($h1->text());
        }

        if (null === $page->getDescription()) {
            $page->setDescription($h1->next('p')->text());
        }

        if ($this->docGenerator->getConfig('treatment.remove-h1', false)) {
            $h1->remove();
            $page->setContents($query->find('html > body')->html());
        }
    }
}