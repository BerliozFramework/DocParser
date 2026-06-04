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

namespace Berlioz\DocParser\Doc;

use Berlioz\DocParser\Doc\File\FileInterface;
use Berlioz\DocParser\Doc\File\FileSet;
use Berlioz\DocParser\Doc\File\Page;
use DateTimeImmutable;
use DateTimeInterface;

class Documentation
{
    private DateTimeImmutable $date;
    private DocSummary $summary;
    private FileSet $files;

    /**
     * DocumentationVersion constructor.
     *
     * @param string $version
     * @param FileSet|null $files
     * @param DateTimeImmutable|null $date
     */
    public function __construct(
        private string $version,
        ?FileSet $files = null,
        ?DateTimeImmutable $date = null
    ) {
        $this->files = $files ?? new FileSet();
        $this->date = $date ?? $this->computeDate($this->files);
        $this->summary = new DocSummary();
    }

    /**
     * Compute date from files.
     *
     * Returns the most recent date among the files. Falls back to the current
     * date when no file exposes a date.
     *
     * @param FileSet $files
     *
     * @return DateTimeImmutable
     */
    private function computeDate(FileSet $files): DateTimeImmutable
    {
        $date = null;

        /** @var FileInterface $file */
        foreach ($files as $file) {
            $fileDate = $file->getDatetime();

            if (null === $fileDate) {
                continue;
            }

            if (null === $date || $fileDate > $date) {
                $date = $fileDate;
            }
        }

        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        return new DateTimeImmutable();
    }

    /**
     * __debugInfo() PHP method.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'date' => $this->date,
            'version' => $this->version,
            'summary' => $this->summary,
            'files' => $this->files
        ];
    }

    /**
     * __serialize() PHP method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'date' => $this->date,
            'version' => $this->version,
            'summary' => $this->summary,
            'files' => $this->files
        ];
    }

    /**
     * __unserialize() PHP method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->date = $data['date'] ?? new DateTimeImmutable();
        $this->version = $data['version'];
        $this->summary = $data['summary'];
        $this->files = $data['files'];
    }

    /**
     * Get date.
     *
     * The date is computed at construction time as the most recent date among
     * the files (or the explicit date passed to the constructor). It is not
     * recomputed when files are added afterwards: create a new Documentation to
     * re-evaluate it.
     *
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get summary.
     *
     * @return DocSummary
     */
    public function getSummary(): DocSummary
    {
        return $this->summary;
    }

    /**
     * Get files.
     *
     * @param callable|null $filter
     *
     * @return FileSet
     */
    public function getFiles(?callable $filter = null): FileSet
    {
        if (null === $filter) {
            return $this->files;
        }

        return $this->files->filter($filter);
    }

    /**
     * Handle.
     *
     * @param string $path
     *
     * @return FileInterface|null
     */
    public function handle(string $path): ?FileInterface
    {
        $file = $this->getFiles()->findByPath($path);

        if ($file instanceof Page) {
            $summaryEntry = $this->getSummary()->findByPage($file);
            $this->getSummary()->setActive($summaryEntry);
        }

        return $file;
    }
}