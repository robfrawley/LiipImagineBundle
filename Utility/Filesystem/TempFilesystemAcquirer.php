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

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final class TempFilesystemAcquirer
{
    /**
     * @var string|null
     */
    private $defaultBasePath;

    /**
     * @var int
     */
    private $setModeBasePath;

    /**
     * @var bool
     */
    private $onlyThrowFatals;

    /**
     * @var \Exception[]
     */
    private $silencedListing;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string|null $defaultBasePath
     * @param int         $setModeBasePath
     * @param bool        $onlyThrowFatals
     */
    public function __construct(string $defaultBasePath = null, int $setModeBasePath = 0777, bool $onlyThrowFatals = true)
    {
        $this->defaultBasePath = $defaultBasePath;
        $this->setModeBasePath = $setModeBasePath;
        $this->onlyThrowFatals = $onlyThrowFatals;
        $this->silencedListing = [];

        $this->filesystem = new Filesystem();
    }

    /**
     * @return string
     */
    public function getTempBasePath(): string
    {
        return $this->defaultBasePath ?? sys_get_temp_dir();
    }

    /**
     * @param string|null $path
     *
     * @throws \Exception|IOException
     *
     * @return string|null
     */
    public function acquireTempBasePath(string $path = null): ?string
    {
        return
            $this->makeModeSetPath($path ?? $this->getTempBasePath()) ??
            $this->makeModeSetPath(sys_get_temp_dir());
    }

    /**
     * @param string $path
     *
     * @throws \Exception
     *
     * @return null|string
     */
    private function makeModeSetPath(string $path): ?string
    {
        if (!$this->filesystem->isAbsolutePath($path)) {
            throw new IOException(sprintf('Temporary base path provided "%s" must be absolute.', $path));
        }

        if ($this->filesystem->exists($path) && !is_writable($path)) {
            throw new IOException(sprintf(''))
        }

        if (!$this->filesystem->exists($path)) {
            try {
                $this->filesystem->mkdir($path, $this->setModeBasePath);
            } catch (IOException $exception) {
                $this->handleException($exception);

                return null;
            }
        }

        try {
            $this->filesystem->chmod($path, $this->setModeBasePath, 0000, true);
        } catch (IOException $exception) {
            $this->handleException($exception);
        }

        return $path;
    }

    /**
     * @param array       $options
     * @param string|null $prefix
     *
     * @return string
     */
    protected function acquireTemporaryFilePath(array $options, string $prefix = null): string
    {
        $root = isset($options['temp_dir']) ? $options['temp_dir'] : ($this->temporaryBasePath ?: sys_get_temp_dir());

        if (!is_dir($root)) {
            try {
                $this->filesystem->mkdir($root);
            } catch (IOException $exception) {
                // ignore failure as "tempnam" function will revert back to system default tmp path as last resort
            }
        }

        if (false === $file = @tempnam($root, $prefix ?: 'post-processor')) {
            throw new \RuntimeException(sprintf('Temporary file cannot be created in "%s"', $root));
        }

        return $file;
    }

    /**
     * @return bool
     */
    public function hasExceptionHistory(): bool
    {
        return !empty($this->silencedListing);
    }

    /**
     * @return \Exception[]
     */
    public function getSilencedListing(): array
    {
        return $this->silencedListing;
    }

    /**
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    private function handleException(\Exception $exception): void
    {
        if ($this->onlyThrowFatals) {
            throw $exception;
        }

        $this->silencedListing[] = $exception;
    }
}
