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

namespace Berlioz\DocParser;

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\RawFile;
use Berlioz\DocParser\Exception\GeneratorException;
use Berlioz\DocParser\Exception\ParserException;
use Berlioz\DocParser\Parser\ParserInterface;
use Berlioz\DocParser\Treatment\DocSummaryTreatment;
use Berlioz\DocParser\Treatment\PageSummaryTreatment;
use Berlioz\DocParser\Treatment\PathTreatment;
use Berlioz\DocParser\Treatment\TitleTreatment;
use Berlioz\DocParser\Treatment\TreatmentInterface;
use DateTimeImmutable;
use Exception;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\Visibility;
use Throwable;

/**
 * Class DocGenerator.
 *
 * @package Berlioz\DocParser
 */
class DocGenerator
{
    protected ParserInterface $parser;
    protected array $config = [];
    protected array $treatments = [];

    /**
     * DocGenerator constructor.
     *
     * @param ParserInterface $parser
     * @param array $config
     */
    public function __construct(ParserInterface $parser, array $config = [])
    {
        $this->parser = $parser;
        $this->config = $config;

        $this->addTreatment(new PathTreatment(), 10);
        $this->addTreatment(new TitleTreatment($this), 20);
        $this->addTreatment(new PageSummaryTreatment(), 50);
        $this->addTreatment(new DocSummaryTreatment(), 50);
    }

    /**
     * Get config.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ??= $default;
    }

    /**
     * Add treatment.
     *
     * @param TreatmentInterface $treatment
     * @param int $priority
     *
     * @return static
     */
    public function addTreatment(TreatmentInterface $treatment, int $priority = 100): DocGenerator
    {
        $this->treatments[$priority][] = $treatment;

        return $this;
    }

    /**
     * Parser accept file?
     *
     * @param FileAttributes $fileAttributes
     *
     * @return bool
     */
    private function parserAcceptFile(FileAttributes $fileAttributes): bool
    {
        $fileName = basename($fileAttributes->path());
        $extensionPos = strripos(basename($fileAttributes->path()), '.');

        if (false !== $extensionPos) {
            if ($this->parser->acceptExtension(substr($fileName, $extensionPos + 1))) {
                return true;
            }
        }

        if (null === $fileAttributes->mimeType()) {
            return false;
        }

        if ($this->parser->acceptExtension($fileAttributes->mimeType())) {
            return true;
        }

        return false;
    }

    /**
     * Handle.
     *
     * @param string $version
     * @param Filesystem $filesystem
     * @param string $location
     *
     * @return Documentation
     * @throws Exception
     */
    public function handle(string $version, Filesystem $filesystem, string $location = '/'): Documentation
    {
        try {
            $documentation = new Documentation($version);

            $listing =
                $filesystem
                    ->listContents($location, Filesystem::LIST_DEEP)
                    ->filter(
                        function (StorageAttributes $attributes) {
                            if (preg_match('#(^|/)\.#', $attributes->path()) === 1) {
                                return false;
                            }

                            return Visibility::PUBLIC === ($attributes->visibility() ?? Visibility::PUBLIC);
                        }
                    )
                    ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
                    ->toArray();

            /** @var FileAttributes $fileAttributes */
            foreach ($listing as $fileAttributes) {
                // Detection of mime
                if (null === $fileAttributes->mimeType()) {
                    $fileAttributes = $fileAttributes->jsonSerialize();
                    $fileAttributes[FileAttributes::ATTRIBUTE_MIME_TYPE] =
                        $filesystem->mimeType($fileAttributes[FileAttributes::ATTRIBUTE_PATH]);

                    $fileAttributes = FileAttributes::fromArray($fileAttributes);
                }

                $file = $this->handleFile($filesystem, $fileAttributes);
                $documentation->getFiles()->addFile($file);
            }

            $this->doTreatments($documentation);

            return $documentation;
        } catch (Throwable $e) {
            throw new GeneratorException('Unable to generate documentation', 0, $e);
        }
    }

    protected function doTreatments(Documentation $documentation): void
    {
        /** @var TreatmentInterface[] $treatments */
        array_walk_recursive(
            $this->treatments,
            fn(TreatmentInterface $treatment) => $treatment->handle($documentation)
        );
    }

    /**
     * Handle file.
     *
     * @param Filesystem $filesystem
     * @param FileAttributes $fileAttributes
     *
     * @return FileInterface
     * @throws FilesystemException
     * @throws ParserException
     */
    protected function handleFile(
        Filesystem $filesystem,
        FileAttributes $fileAttributes
    ): FileInterface {
        // Parser do not accept file? Raw file
        if (!$this->parserAcceptFile($fileAttributes)) {
            return
                new RawFile(
                    $filesystem->readStream($fileAttributes->path()),
                    $fileAttributes->path(),
                    $fileAttributes->mimeType(),
                    $fileAttributes->lastModified() ?
                        (new DateTimeImmutable())->setTimestamp($fileAttributes->lastModified()) : null
                );
        }

        return $this->parser->parse($filesystem->read($fileAttributes->path()), $fileAttributes);
    }
}