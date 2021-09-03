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
use Berlioz\HtmlSelector\Exception\HtmlSelectorException;
use Berlioz\HtmlSelector\HtmlSelector;

class PathTreatment implements TreatmentInterface
{
    use PathTreatmentTrait;

    private HtmlSelector $htmlSelector;

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
        foreach ($documentation->getFiles()->filter(fn($file) => $file instanceof Page) as $page) {
            $query = $this->htmlSelector->query($page->getContents());

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

        $linkedFile =
            $documentation
                ->getFiles()
                ->findByFilename($absolutePath);

        if (null !== $linkedFile) {
            $anchor = null;
            if (str_contains($absolutePath, '#')) {
                $anchor = '#' . explode('#', $absolutePath, 2)[1];
            }
            $absolutePath = $linkedFile->getPath() . ($anchor ?? '');
        }

        return $this->resolveRelativePath('/' . $page->getPath(), '/' . $absolutePath);
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

        if (str_starts_with($url['path'], '/')) {
            return $path;
        }

        $path = $url['path'];
        if (isset($url['fragment'])) {
            $path .= '#' . $url['fragment'];
        }

        return $this->resolveAbsolutePath($file->getFilename(), $path);
    }
}