<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Filesystem;

use Liip\ImagineBundle\Exception\Filesystem\FilesystemException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class TemporaryFile
{
    /**
     * @var UuidInterface
     */
    private $uuid;

    /**
     * @var string
     */
    private $root;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var \SplFileInfo|null
     */
    private $file;

    /**
     * @var bool
     */
    private $auto;

    /**
     * @param string|null $path Directory path where the temporary file should be written out to. A "null" value causes
     *                          the path to be set using sys_get_temp_dir() and a relative path (one that is determined
     *                          to be non-absolute and non-uri) will be appended to sys_get_temp_dir().
     * @param string|null $name File name prefix that will be used in the construction of a random file name. The final
     *                          name uses "imagine-bundle_%06d-%06d_%s_%s.%s" format (where your name value is replaced
     *                          in the first placeholder).
     * @param string|null $type File type (generally an extension) that will be used to provide the constructed temp
     *                          file's extension. Only alphanumeric characters, dashes, underscores, and periods are
     *                          allowed (anything outside this will be stripped from the string).
     * @param string|null $blob File contents to immediately write out in the temporary file. A "null" value causes lazy
     *                          random file name generation where you must explicitly call acquire() prior to
     *                          getContents(), setContents(), and other accessors. Providing any value, even an empty
     *                          string, results in immediate acquisition of a unique file path.
     * @param bool $auto        Toggles whether to automatically release (and by default, remove) the temporary file on
     *                          object deconstruction. If disabled, you must manually call release() to handle removal
     *                          of the temporary file.
     */
    public function __construct(string $path = null, string $name = null, string $type = null, string $blob = null, bool $auto = true)
    {
        $this->root = self::normalizePathInput($path);
        $this->name = self::normalizeNameInput($name);
        $this->type = self::normalizeTypeInput($type);
        $this->auto = $auto;

        if (null !== $blob) {
            $this->setContents($blob);
        }
    }

    /**
     * Automatically release the temporary file if requested to do so.
     */
    public function __destruct()
    {
        if ($this->auto) {
            $this->release();
        }
    }

    /**
     * @return null|UuidInterface
     */
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @return null|\SplFileInfo
     */
    public function getFile(): ?\SplFileInfo
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function stringifyFile(): ?string
    {
        return $this->isAcquired() ? $this->file->getPathname() : null;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->clearStatCache()->isAcquired() && file_exists($this->stringifyFile());
    }

    /**
     * @return int
     */
    public function getBytes(): int
    {
        return $this->exists() ? @filesize($this->stringifyFile()) ?: 0 : 0;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getContents(): ?string
    {
        if ($this->isAcquired() && false === $blob = @file_get_contents($this->stringifyFile())) {
            throw self::createExceptionInstance('Failed read operation', $this->stringifyFile());
        }

        return $blob ?? null ?: null;
    }

    /**
     * @param string|null $blob
     * @param bool        $append
     *
     * @return self
     */
    public function setContents(string $blob = null, bool $append = false): self
    {
        if (!$this->isAcquired()) {
            $this->acquire();
        }

        return $this->write($blob, $append);
    }

    /**
     * @param string|null $blob
     *
     * @return self
     */
    public function addContents(string $blob = null): self
    {
        return $this->setContents($blob, true);
    }

    /**
     * @return self
     */
    public function acquire(): self
    {
        if (!$this->isAcquired()) {
            $this->acquireUniqueIdentifiers();
        }

        if (!$this->exists()) {
            $this->create();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isAcquired(): bool
    {
        return null !== $this->file;
    }

    /**
     * @param bool $remove
     *
     * @return self
     */
    public function release(bool $remove = true): self
    {
        if ($this->isAcquired()) {
            $this->releaseUniqueIdentifiers($remove);
        }

        return $this;
    }

    /**
     * @param string|null $blob
     * @param bool        $append
     *
     * @return self
     */
    private function write(string $blob = null, bool $append = false): self
    {
        if (false === @file_put_contents($this->stringifyFile(), $blob ?? '', $append ? FILE_APPEND : 0)) {
            throw self::createExceptionInstance('Failed write operation', $this->stringifyFile());
        }

        return $this;
    }

    /**
     * @return self
     */
    private function create(): self
    {
        $path = $this->file->getPath();

        if (!is_dir($path) && !@mkdir($path, 0777, true)) {
            throw self::createExceptionInstance('Failed create operation', $path);
        }

        if (!is_writable($path)) {
            throw self::createExceptionInstance('Failed writable check', $path);
        }

        return $this->write();
    }

    /**
     * @return void
     */
    private function acquireUniqueIdentifiers(): void
    {
        do {
            $this->file = new \SplFileInfo(vsprintf('%s/imagine-bundle-%s-%s.%s', [
                $this->root,
                $this->name,
                $this->uuid = Uuid::uuid4(),
                $this->type,
            ]));
        } while ($this->exists());
    }

    /**
     * @param bool $remove
     *
     * @return void
     */
    private function releaseUniqueIdentifiers(bool $remove): void
    {
        if ($remove && $this->exists() && (false === @unlink($this->stringifyFile()) || $this->exists())) {
            throw self::createExceptionInstance('Failed remove operation', $this->stringifyFile());
        }

        $this->file = null;
        $this->uuid = null;
    }

    /**
     * @return self
     */
    private function clearStatCache(): self
    {
        if ($this->isAcquired()) {
            clearstatcache(true, $this->stringifyFile());
        }

        return $this;
    }

    /**
     * @param string|null $value
     *
     * @return string
     */
    private static function normalizePathInput(string $value = null): string
    {
        return self::buildAbsolutePath(
            self::normalizeInput(self::parseUriType($value), [
                ['/[^0-9a-z\._-]+/i', '-'],
            ], null),
            self::normalizeInput(self::parseUriPath($value), [
                ['/([\/\\\\])\1+/', '/'],
                ['/[\/\\\\]\z/', ''],
            ], sys_get_temp_dir())
        );
    }

    /**
     * @param string|null $value
     *
     * @return string
     */
    private static function normalizeNameInput(string $value = null): string
    {
        return self::normalizeInput($value, [
            ['/[^0-9a-z\._-]+/i', '-'],
            ['/([^a-z0-9])\1+/', '\1', 4],
            ['/(\A[^0-9a-z]+)|([^0-9a-z]+\z)/i', ''],
        ], 'working-file');
    }

    /**
     * @param string|null $value
     *
     * @return string
     */
    private static function normalizeTypeInput(string $value = null): string
    {
        return self::normalizeInput($value, [
            ['/[^0-9a-z\._-]+/i', ''],
            ['/([^a-z0-9])\1+/', '\1', 4],
        ], 'temporary');
    }

    /**
     * @param string|null $value
     * @param array       $instructions
     * @param string|null $default
     *
     * @return string|null
     */
    private static function normalizeInput(string $value = null, array $instructions = [], string $default = null): ?string
    {
        if (null !== $value) {
            foreach ($instructions as $step) {
                for ($j = 0; $j < ($step[2] ?? 1); $j++) {
                    $value = preg_replace($step[0], $step[1], $value);
                }
            }
        }

        return $value ?: $default;
    }

    /**
     * @param string|null $uri
     *
     * @return array
     */
    private static function parseUri(string $uri = null): array
    {
        preg_match('/\A(?:(?<scheme>[\w]+):)?(?<path>(?:[\/\\\\]+)?.+)/i', $uri, $matches);

        return $matches;
    }

    /**
     * @param string|null $uri
     *
     * @return null|string
     */
    private static function parseUriType(string $uri = null): ?string
    {
        return self::parseUri($uri)['scheme'] ?? null ?: null;
    }

    /**
     * @param string|null $uri
     *
     * @return null|string
     */
    private static function parseUriPath(string $uri = null): ?string
    {
        return self::parseUri($uri)['path'] ?? null ?: null;
    }

    /**
     * @param string|null $type
     * @param string|null $path
     *
     * @return null|string
     */
    private static function buildAbsolutePath(string $type = null, string $path = null): ?string
    {
        if (substr($path, 0, 1) !== '/') {
            $path = sprintf('%s/%s', sys_get_temp_dir(), $path);
        }

        return $type ? sprintf('%s:/%s', $type, $path) : $path;
    }

    /**
     * @param string      $format
     * @param string|null $path
     * @param mixed       ...$replacements
     *
     * @return FilesystemException
     */
    private static function createExceptionInstance(string $format, string $path = null, ...$replacements): FilesystemException
    {
        $exceptionMessage = @vsprintf($format, $replacements) ?: $format;

        if (null !== $path) {
            $exceptionMessage .= sprintf(': "%s"', $path);
        }

        if ($last = error_get_last()['message'] ?? false) {
            $exceptionMessage .= sprintf(' (%s)', $last);
            error_clear_last();
        }

        return new FilesystemException($exceptionMessage);
    }
}
