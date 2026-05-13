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

namespace Berlioz\DocParser\Parser\DoctrineRst;

use Doctrine\RST\Nodes\Node;

class IndexNode extends Node
{
    /** @var array<string, string> */
    private array $options;

    /**
     * @param array<string, string> $options
     */
    public function __construct(array $options)
    {
        parent::__construct();
        $this->options = $options;
    }

    /**
     * Get options.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    protected function doRender(): string
    {
        return '';
    }
}
