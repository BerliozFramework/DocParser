<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\DocParser\Parser;

use Berlioz\DocParser\Exception\ParserException;
use Berlioz\DocParser\File\FileInterface;
use Berlioz\DocParser\File\Page;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extras\CommonMarkExtrasExtension;

class Markdown implements ParserInterface
{
    /** @var \League\CommonMark\CommonMarkConverter Parser */
    private $commonMarkConverter;

    /**
     * Get ParseDownExtra library.
     *
     * @return \League\CommonMark\CommonMarkConverter
     * @throws \Berlioz\DocParser\Exception\DocParserException
     */
    protected function getCommonMarkConverter(): CommonMarkConverter
    {
        if (is_null($this->commonMarkConverter)) {
            try {
                $environment = Environment::createCommonMarkEnvironment();
                $environment->addExtension(new CommonMarkExtrasExtension());

                $config = [];
                $this->commonMarkConverter = new CommonMarkConverter($config, $environment);
            } catch (\Throwable $e) {
                throw new ParserException('Error during initilization of Markdown parser', 0, $e);
            }
        }

        return $this->commonMarkConverter;
    }

    ////////////////////////
    /// PARSER INTERFACE ///
    ////////////////////////

    /**
     * @inheritdoc
     */
    public function acceptMime(string $mime): bool
    {
        return in_array($mime, ['text/markdown', 'text/x-markdown']);
    }

    /**
     * @inheritdoc
     */
    public function acceptExtension(string $extension): bool
    {
        return in_array($extension, ['md']);
    }

    /**
     * @inheritdoc
     */
    public function parse(FileInterface $srcFile): FileInterface
    {
        try {
            $page = new Page($srcFile);
            $page->setParsedContent((string) $this->getCommonMarkConverter()->convertToHtml($page->getContent()))
                 ->setMime('text/html');

            return $page;
        } catch (\Exception $e) {
            throw new ParserException('An error occurred during parsing of content', 0, $e);
        }
    }
}