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

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Parser\Markdown;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected ?FakeDocGenerator $docGenerator = null;
    protected ?Documentation $documentation = null;

    protected function getDocGenerator(): FakeDocGenerator
    {
        if (null !== $this->docGenerator) {
            return $this->docGenerator;
        }

        $this->docGenerator = new FakeDocGenerator();
        $this->docGenerator->addParser(new Markdown());

        return $this->docGenerator;
    }

    protected function getDocumentation(): Documentation
    {
        if (null !== $this->documentation) {
            return $this->documentation;
        }

        $adapter = new LocalFilesystemAdapter(realpath(__DIR__ . '/../_test'));
        $filesystem = new Filesystem($adapter);

        $this->documentation = $this->getDocGenerator()->handle('current', $filesystem);

        return $this->documentation;
    }
}