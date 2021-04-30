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

namespace Berlioz\DocParser\Tests\Parser;

use Berlioz\DocParser\Parser\TraitCastValue;
use PHPUnit\Framework\TestCase;

class TraitCastValueTest extends TestCase
{
    private function getTrait()
    {
        return new class {
            use TraitCastValue {
                castValue as public;
            }
        };
    }

    public function testCastValue_boolean()
    {
        $this->assertTrue($this->getTrait()->castValue(true));
        $this->assertTrue($this->getTrait()->castValue(' true  '));
        $this->assertFalse($this->getTrait()->castValue(false));
        $this->assertFalse($this->getTrait()->castValue('false'));
    }

    public function testCastValue_int()
    {
        $this->assertSame(123456, $this->getTrait()->castValue(123456));
        $this->assertSame(123456, $this->getTrait()->castValue('123456'));
        $this->assertSame(123456, $this->getTrait()->castValue(' 123456'));
    }

    public function testCastValue_float()
    {
        $this->assertSame(.123456, $this->getTrait()->castValue(.123456));
        $this->assertSame(.123456, $this->getTrait()->castValue(' .123456'));
        $this->assertSame(.123456, $this->getTrait()->castValue('.123456'));
    }

    public function testCastValue_string()
    {
        $this->assertSame('foo', $this->getTrait()->castValue('foo'));
        $this->assertSame('foo', $this->getTrait()->castValue('foo  '));
    }
}
