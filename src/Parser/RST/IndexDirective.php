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

namespace Berlioz\DocParser\Parser\RST;

use Gregwar\RST\Directive;
use Gregwar\RST\Parser;

class IndexDirective extends Directive
{
    public function getName(): string
    {
        return 'index';
    }

    public function processNode(Parser $parser, $variable, $data, array $options): IndexNode
    {
        return new IndexNode($options);
    }
}