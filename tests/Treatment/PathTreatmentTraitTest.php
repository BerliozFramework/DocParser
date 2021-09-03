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

namespace Berlioz\DocParser\Tests\Treatment;

use Berlioz\DocParser\Treatment\PathTreatmentTrait;
use PHPUnit\Framework\TestCase;

class PathTreatmentTraitTest extends TestCase
{
    public function absolutePathProvider()
    {
        return [
            ['index.md', 'foo/bar/foo.md', 'foo/bar/foo.md'],
            ['/index.md', '/foo/bar/baz.md', 'foo/bar/baz.md'],
            ['/index.md', 'bar.md', 'bar.md'],
            ['/index.md', '/baz.md', 'baz.md'],
            ['foo/bar/index.md', 'baz/qux.md', 'foo/bar/baz/qux.md'],
            ['foo/bar/index.md', '/baz/qux.md', 'baz/qux.md'],
            ['foo/bar/index.md', './baz.md', 'foo/bar/baz.md'],
            ['foo/bar/index.md', '../baz.md', 'foo/baz.md'],
            ['foo/bar/index.md', '../../qux.md', 'qux.md'],
            ['foo/bar/index', '../../qux/quux.foo', 'qux/quux.foo'],
            ['foo/bar/index.md', '../../../qux.md', null],
            ['foo/bar/index', '../../qux/quux.foo#anchor', 'qux/quux.foo#anchor'],
        ];
    }

    /**
     * @param $src
     * @param $dst
     * @param $excepted
     *
     * @dataProvider absolutePathProvider
     */
    public function testResolveAbsolutePath($src, $dst, $excepted)
    {
        /** @var PathTreatmentTrait $pathTreatment */
        $pathTreatment = $this->getMockForTrait(PathTreatmentTrait::class);

        $this->assertEquals($excepted, $pathTreatment->resolveAbsolutePath($src, $dst));
    }

    public function relativePathProvider()
    {
        return [
            ['index.md', 'foo/bar/foo.md', './foo/bar/foo.md'],
            ['index.md', '/foo/bar/baz.md', './foo/bar/baz.md'],
            ['/index.md', 'foo/bar/bar.md', './foo/bar/bar.md'],
            ['/index.md', 'bar.md', './bar.md'],
            ['/index.md', '/baz.md', './baz.md'],
            ['./index.md', '/qux.md', './qux.md'],
            ['index.md', 'quux.md', './quux.md'],
            ['foo/index.md', '/foo/baz.md', './baz.md'],
            ['./foo/bar/index.md', '/baz.md', '../../baz.md'],
            ['/foo/bar/index.md', '/qux/baz.md', '../../qux/baz.md'],
            ['/foo/bar/index.md', 'qux/baz.md', './qux/baz.md'],
            ['/foo/bar/quux/index.md', '/foo/qux/corge/baz.md', '../../qux/corge/baz.md'],
            ['./foo/index.md', './bar/baz.md', './bar/baz.md'],
            ['foo/index.md', '/bar/baz.md', '../bar/baz.md'],
            ['./foo/index.md', '../bar/baz.md', '../bar/baz.md'],
            ['./foo/index.md', '../foo/baz.md', './baz.md'],
            ['./foo/index.md', '../foo/baz.md#anchor', './baz.md#anchor'],
        ];
    }

    /**
     * @dataProvider relativePathProvider
     */
    public function testResolveRelativePath($src, $dst, $excepted)
    {
        /** @var PathTreatmentTrait $pathTreatment */
        $pathTreatment = $this->getMockForTrait(PathTreatmentTrait::class);

        $this->assertEquals($excepted, $pathTreatment->resolveRelativePath($src, $dst));
    }
}
