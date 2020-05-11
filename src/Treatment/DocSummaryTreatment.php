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

class DocSummaryTreatment implements TreatmentInterface
{
    /**
     * @inheritDoc
     */
    public function handle(Documentation $documentation): void
    {
        /** @var Page $page */
        foreach ($documentation->getFiles()->filter(fn($file) => $file instanceof Page) as $page) {
            $documentation->getSummary()->addPage($page);
        }
    }
}