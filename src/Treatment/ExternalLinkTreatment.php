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
 * Class ExternalLinkTreatment.
 *
 * @package Berlioz\DocParser\Treatment
 */
class ExternalLinkTreatment implements TreatmentInterface
{
    private DocGenerator $docGenerator;

    /**
     * TitleTreatment constructor.
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
            $this->doExternalLinksTreatment($page);
        }
    }

    /**
     * Do external links treatment.
     *
     * @param Page $page
     *
     * @throws QueryException
     * @throws SelectorException
     */
    public function doExternalLinksTreatment(Page $page)
    {
        $query = Query::loadHtml((string)$page->getContents());
        $links = $query->find('a[href]');

        if (0 === count($links)) {
            return;
        }

        foreach ($links as $link) {
            // Not an external link?
            if (!$this->isExternalLink($link->attr('href'))) {
                continue;
            }

            if (false !== $this->docGenerator->getConfig('treatment.external-links.target')) {
                $link->attr('target', $this->docGenerator->getConfig('treatment.external-links.target', '_blank'));
            }

            if (false !== $this->docGenerator->getConfig('treatment.external-links.rel')) {
                $link->attr('rel', $this->docGenerator->getConfig('treatment.external-links.rel', 'nofollow noopener'));
            }

            $page->setContents($query->find('html > body')->html());
        }
    }

    /**
     * Is external link?
     *
     * @param string $url
     *
     * @return bool
     */
    public function isExternalLink(string $url): bool
    {
        $url = parse_url($url);

        // Not a valid url
        if (false === $url) {
            return false;
        }

        // Relative url
        if (!isset($url['host'])) {
            return false;
        }

        // In authorized hosts
        foreach ($this->docGenerator->getConfig('treatment.external-links.hosts', []) as $host) {
            if (substr($host, 0, 1) === '.') {
                $hostLength = strlen($host);

                if (substr(('.' . $url['host']), -$hostLength) === $host) {
                    return false;
                }

                continue;
            }

            if ($url['host'] === $host) {
                return false;
            }
        }
        if (in_array($url['host'], $this->docGenerator->getConfig('treatment.external-links.hosts', []))) {
            return false;
        }

        return true;
    }
}