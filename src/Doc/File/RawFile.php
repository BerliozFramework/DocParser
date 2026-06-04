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

namespace Berlioz\DocParser\Doc\File;

use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream;
use DateTimeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class RawFile implements FileInterface
{
    protected string $hash;

    /**
     * RawFile constructor.
     *
     * @param resource $stream
     * @param string $filename
     * @param string|null $mime
     * @param DateTimeInterface|null $datetime
     */
    public function __construct(
        protected $stream,
        protected string $filename,
        protected ?string $mime = null,
        protected ?DateTimeInterface $datetime = null
    ) {
        $this->hash = md5($this->filename);
    }

    /**
     * PHP Magic method.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'filename' => $this->filename,
            'mime' => $this->mime,
            'datetime' => $this->datetime,
            'content' => $this->stream,
            'hash' => $this->hash,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __serialize(): array
    {
        return [
            'filename' => $this->filename,
            'mime' => $this->mime,
            'datetime' => $this->datetime,
            'hash' => $this->hash,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        $this->filename = $data['filename'];
        $this->mime = $data['mime'];
        $this->datetime = $data['datetime'];
        $this->hash = $data['hash'];
    }

    //////////////////////
    /// FILE INTERFACE ///
    //////////////////////

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return str_replace('\\', '/', $this->getFilename());
    }

    /**
     * @inheritDoc
     */
    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * @inheritDoc
     */
    public function getDatetime(): ?DateTimeInterface
    {
        return $this->datetime;
    }

    /**
     * Resolve the stream.
     *
     * If a lazy provider (callable) was set, invoke it on first access, validate
     * that it returns a resource, and memoize the result so the underlying read
     * (e.g. a remote S3 GET) happens only once.
     *
     * @return resource
     */
    private function resolveStream()
    {
        if (is_callable($this->stream)) {
            $stream = ($this->stream)();

            if (!is_resource($stream)) {
                throw new InvalidArgumentException('Stream provider must return a valid stream resource');
            }

            $this->stream = $stream;
        }

        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        return $this->resolveStream();
    }

    /**
     * @inheritDoc
     */
    public function setStream($stream): void
    {
        if (!is_resource($stream) && !is_callable($stream)) {
            throw new InvalidArgumentException('Argument must be a valid stream resource or a callable provider');
        }

        $this->stream = $stream;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        if (($contents = stream_get_contents($this->resolveStream(), -1, 0)) === false) {
            throw new RuntimeException('Unable to get contents of stream');
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function setContents(string $contents): void
    {
        $stream = $this->resolveStream();

        if (@ftruncate($stream, 0) === false) {
            throw new RuntimeException('Unable to truncate contents of stream');
        }

        rewind($stream);

        if (@fwrite($stream, $contents) === false) {
            throw new RuntimeException('Unable to write contents of stream');
        }
    }

    /**
     * @inheritDoc
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @inheritDoc
     */
    public function response(?ResponseInterface $response = null): ResponseInterface
    {
        if (null === $response) {
            $response = new Response();
        }

        // Body content
        $stream = new Stream($this->getStream());

        return
            $response
                ->withBody($stream)
                ->withHeader('Content-Type', $this->getMime());
    }
}