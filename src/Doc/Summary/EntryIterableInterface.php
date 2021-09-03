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

namespace Berlioz\DocParser\Doc\Summary;

use Countable;
use IteratorAggregate;

interface EntryIterableInterface extends Countable, IteratorAggregate
{
    /**
     * __serialize() PHP method.
     *
     * @return array
     */
    public function __serialize(): array;

    /**
     * __unserialize() PHP method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void;

    /**
     * Count recursive entries.
     *
     * @return int
     */
    public function countRecursive(): int;
}