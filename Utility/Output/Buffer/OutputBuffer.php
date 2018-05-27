<?php

/*
 * This file is part of the `src-run/augustus-utility-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Output\Buffer;

use Liip\ImagineBundle\Exception\Utility\Output\Buffer\OutputException;
use Liip\ImagineBundle\Utility\Filesystem\TemporaryFile;

final class OutputBuffer
{
    /**
     * @var int|float
     */
    private $lBytes;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var resource
     */
    private $buffer;

    /**
     * Creates a "memory buffered output handler" instance. Passing "null" (the default) results in a force-memory-only
     * buffer, whereas passing any "float" value causes memory-mode until the configured threshold is reach, after which
     * it falls back to file-based buffering.
     *
     * @param float $maximumMegabytes
     */
    public function __construct(float $maximumMegabytes = null)
    {
        $this->lBytes = null === $maximumMegabytes ? null : (int) $maximumMegabytes * 1000 * 1000;
        $this->scheme = null === $this->lBytes ? 'php://memory' : sprintf('php://temp/maxmemory:%d', $this->lBytes);
        $this->reset();
    }

    /**
     * Creates a "memory buffered output handler" instance that operates in-memory until its buffer reaches a defined
     * threshold (by default this is 8 megabits), after which it falls back to file-based buffering.
     *
     * @param string|null $contents
     * @param float       $maximumMegabytes
     *
     * @return OutputBuffer
     */
    public static function createBufferMemoryLimited(string $contents = null, float $maximumMegabytes = 8): self
    {
        $buffer = new self($maximumMegabytes);

        if (null !== $contents) {
            $buffer->add($contents);
        }

        return $buffer;
    }

    /**
     * Creates a "memory buffered output handler" instance that operates in-memory regardless of the size the buffer
     * reaches. Be mindful when using this mode, as it can theoretically consume enough memory to hit the configured
     * PHP max-memory setting.
     *
     * @param string|null $contents
     *
     * @return self
     */
    public static function createBufferMemoryOnly(string $contents = null): self
    {
        $buffer = new self();

        if (null !== $contents) {
            $buffer->add($contents);
        }

        return $buffer;
    }

    /**
     * Ensure the file resource is closed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->get();
    }

    /**
     * @return int|null
     */
    public function getMaximumBytes(): ?int
    {
        return $this->lBytes;
    }

    /**
     * @return bool
     */
    public function hasMaximumBytes(): bool
    {
        return null !== $this->lBytes;
    }

    /**
     * @return int
     */
    public function getUsedBytes(): int
    {
        return (new TemporaryFile(null, 'output-buffer-size', null, $this->get()))->getBytes();
    }

    /**
     * @return int|null
     */
    public function getAvailableBytes(): ?int
    {
        return $this->hasMaximumBytes() ? $this->getMaximumBytes() - $this->getUsedBytes() : null;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return resource|null
     */
    public function getResource()
    {
        return $this->buffer;
    }

    /**
     * @return bool
     */
    public function isResourceOpen(): bool
    {
        return is_resource($this->buffer);
    }

    /**
     * @param string $content
     * @param bool   $newline
     *
     * @return self
     */
    public function add(string $content, bool $newline = false): self
    {
        if (!$this->isResourceOpen()) {
            throw new OutputException(sprintf(
                'Failed to write "%s" data to closed buffer: re-open the buffer resource using the "%s::reset()" method.',
                mb_strlen($content) > 40 ? sprintf('%s [...]', mb_substr($content, 0, 40)) : $content, __CLASS__
            ));
        }

        fwrite($this->buffer, $newline ? $content.PHP_EOL : $content);

        return $this;
    }

    /**
     * @param int|null $length
     *
     * @return string
     */
    public function get(int $length = null): string
    {
        if (!$this->isResourceOpen()) {
            throw new OutputException(sprintf(
                'Failed to read "%s" data from closed buffer: re-open the buffer resource using the "%s::reset()" method.',
                $length ? sprintf('%d bytes', $length) : 'all', __CLASS__
            ));
        }

        rewind($this->buffer);

        return (null === $length ? stream_get_contents($this->buffer) : fread($this->buffer, $length)) ?: '';
    }

    /**
     * @return self
     */
    public function reset(): self
    {
        $this->close();
        $this->buffer = fopen($this->scheme, 'r+b');

        return $this;
    }

    /**
     * @return self
     */
    public function close(): self
    {
        if ($this->isResourceOpen()) {
            @fclose($this->buffer);
        }

        $this->buffer = null;

        return $this;
    }
}
