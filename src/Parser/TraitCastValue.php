<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\DocParser\Parser;

/**
 * Trait TraitCastValue.
 */
trait TraitCastValue
{
    /**
     * Cast value.
     *
     * @param $value
     *
     * @return mixed|string
     */
    protected function castValue($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (null !== ($tmp = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
            return $tmp;
        }

        if (false !== ($tmp = filter_var($value, FILTER_VALIDATE_INT))) {
            return $tmp;
        }

        if (false !== ($tmp = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            return $tmp;
        }

        return $value;
    }
}