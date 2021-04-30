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

use Berlioz\DocParser\Parser\TraitCastValue;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;

class IndexProcessor
{
    use TraitCastValue;

    private EnvironmentInterface $environment;
    private array $index = [];

    public function __construct(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param DocumentParsedEvent $event
     *
     * @return void
     */
    public function __invoke(DocumentParsedEvent $event)
    {
        $this->index = [];

        $walker = $event->getDocument()->walker();
        while ($event = $walker->next()) {
            if ($event->isEntering() && $event->getNode() instanceof FencedCode) {
                /** @var FencedCode $code */
                $code = $event->getNode();

                if ($code->getInfo() !== 'index') {
                    continue;
                }

                $code->detach();


                $content = $code->getStringContent();
                $metas = preg_split('/\v/u', $content);
                $metas = array_filter($metas);
                array_walk($metas, fn(&$value) => $value = explode(':', $value, 2));
                array_walk($metas, fn(&$value) => $value = array_pad($value, 2, ''));
                array_walk($metas, fn(&$value) => $value[0] = mb_strtolower($value[0]));
                array_walk_recursive($metas, fn(&$value) => $value = $this->castValue($value));

                $this->index = array_replace($this->index, array_column($metas, 1, 0));
            }
        }
    }

    public function getIndex(): array
    {
        return $this->index;
    }
}