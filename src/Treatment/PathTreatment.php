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
use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\HtmlSelector\Exception\QueryException;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\Query;

class PathTreatment extends AbstractPathTreatment implements TreatmentInterface
{
    /**
     * @inheritDoc
     * @throws QueryException
     * @throws SelectorException
     */
    public function handle(Documentation $documentation): void
    {
        /** @var Page $page */
        foreach ($documentation->getFiles()->filter(fn($file) => $file instanceof Page) as $page) {
            $query = Query::loadHtml($page->getContents());

            foreach ($query->find('[href]') as $link) {
                $link->attr('href', $this->getPathResolved($link->attr('href'), $page, $documentation));
            }

            foreach ($query->find('[src]') as $link) {
                $link->attr('src', $this->getPathResolved($link->attr('src'), $page, $documentation));
            }

            $page->setContents($query->find('html > body')->html());
        }
    }

    /**
     * Get path resolved.
     *
     * @param string $path
     * @param Page $page
     * @param Documentation $documentation
     *
     * @return string
     */
    public function getPathResolved(string $path, Page $page, Documentation $documentation): string
    {
        $absolutePath = $this->getAbsolutePath($path, $page);

        if (null === $absolutePath) {
            return $path;
        }

        $linkedPage =
            $documentation
                ->getFiles()
                ->findByFilename($absolutePath);

        if (null === $linkedPage) {
            return $this->resolveRelativePath('/' . $page->getPath(), '/' . $absolutePath);
        }

        return $this->resolveRelativePath('/' . $page->getPath(), '/' . $linkedPage->getPath());
    }

    /**
     * Get absolute path.
     *
     * @param string $path
     * @param FileInterface $file
     *
     * @return string|null
     */
    private function getAbsolutePath(string $path, FileInterface $file): ?string
    {
        $url = parse_url($path);

        if (isset($url['scheme']) || isset($url['host'])) {
            return null;
        }

        if (!isset($url['path'])) {
            return null;
        }

        if (substr($url['path'], 0, 1) === '/') {
            return $path;
        }

        return $this->resolveAbsolutePath($file->getFilename(), $url['path']);
    }
}