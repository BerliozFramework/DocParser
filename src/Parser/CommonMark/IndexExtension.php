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

namespace Berlioz\DocParser\Parser\CommonMark;

use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

class IndexExtension implements ExtensionInterface
{
    private IndexProcessor $indexProcessor;

    /**
     * @inheritDoc
     */
    public function register(ConfigurableEnvironmentInterface $environment)
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            $this->indexProcessor = new IndexProcessor($environment)
        );
    }

    public function getIndex(): array
    {
        return $this->indexProcessor->getIndex();
    }
}