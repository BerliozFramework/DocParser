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


use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\DocGenerator;
use Berlioz\DocParser\Parser\Markdown;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

trait TraitFakeDocumentation
{
    private ?Documentation $documentation = null;

    /**
     * Get fake documentation.
     *
     * @return Documentation
     * @throws \Exception
     */
    protected function getFakeDocumentation(): Documentation
    {
        if (null !== $this->documentation) {
            return $this->documentation;
        }

        $adapter = new LocalFilesystemAdapter(realpath(__DIR__ . '/_test'));
        $filesystem = new Filesystem($adapter);

        $generator = new DocGenerator();
        $generator->addParser(new Markdown());
        $this->documentation = $generator->handle('current', $filesystem);

        return $this->documentation;
    }
}