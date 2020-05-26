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

namespace Berlioz\DocParser;

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Treatment\PathTreatmentTrait;
use Berlioz\HtmlSelector\Exception\QueryException;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\Query;

/**
 * Class DocIntegrity.
 *
 * @package Berlioz\DocParser
 */
class DocIntegrity
{
    use PathTreatmentTrait;

    /**
     * Check integrity of page.
     *
     * @param Documentation $documentation
     * @param Page $page
     *
     * @return array
     * @throws QueryException
     * @throws SelectorException
     */
    public function checkPage(Documentation $documentation, Page $page): array
    {
        $errors = [];
        $queryHtml = Query::loadHtml($page->getContents());

        // Get links
        foreach ($queryHtml->find('a[href]') as $link) {
            $url = parse_url($link->attr('href'));

            // External link
            if (isset($url['host']) || !isset($url['path'])) {
                continue;
            }
//var_dump($page->getPath(), $url['path'], $this->resolveAbsolutePath($page->getPath(), $url['path']), '#######');
            $file = $documentation->handle('/'.$this->resolveAbsolutePath($page->getPath(), $url['path']));

            if (null !== $file) {
                continue;
            }

            $errors[] = [
                'page' => $page,
                'type' => 'link',
                'path' => $url['path'],
            ];
        }

        // Get media
        foreach ($queryHtml->find('[src]') as $media) {
            $url = parse_url($media->attr('src'));

            // External link
            if (isset($url['host']) || !isset($url['path'])) {
                continue;
            }

            $file = $documentation->handle($this->resolveAbsolutePath($page->getPath(), $url['path']));

            if (null !== $file) {
                continue;
            }

            $errors[] = [
                'page' => $page,
                'type' => 'media',
                'path' => $url['path'],
            ];
        }

        return $errors;
    }

    /**
     * Check integrity of documentation.
     *
     * @param Documentation $documentation
     *
     * @return array
     * @throws QueryException
     * @throws SelectorException
     */
    public function check(Documentation $documentation)
    {
        $errors = [];

        foreach ($documentation->getFiles(fn(FileInterface $file) => $file instanceof Page) as $page) {
            $errors += $this->checkPage($documentation, $page);
        }

        return $errors;
    }
}