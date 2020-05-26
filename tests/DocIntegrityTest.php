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

namespace Berlioz\DocParser\Tests;

use Berlioz\DocParser\DocIntegrity;
use PHPUnit\Framework\TestCase;

class DocIntegrityTest extends TestCase
{
    use TraitFakeDocumentation;

    public function testCheck()
    {
        $docIntegrity = new DocIntegrity();
        $result = $docIntegrity->check($this->getFakeDocumentation());

        $this->assertCount(2, $result);
        $this->assertEquals('hospes/magnis', $result[0]['page']->getPath());
        $this->assertEquals('link', $result[0]['type']);
        $this->assertEquals('./usum.md', $result[0]['path']);

        $this->assertEquals('hospes/magnis', $result[1]['page']->getPath());
        $this->assertEquals('media', $result[1]['type']);
        $this->assertEquals('../assets/magnis.jpg', $result[1]['path']);
    }
}
