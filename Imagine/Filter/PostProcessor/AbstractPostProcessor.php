<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Imagine\Filter\PostProcessor;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\FileBinaryInterface;
use Liip\ImagineBundle\Utility\Pcre\Matcher\MultiplePcreMatcher;
use Liip\ImagineBundle\Utility\Process\DescribeProcess;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
abstract class AbstractPostProcessor implements PostProcessorInterface
{
    /**
     * @var string
     */
    protected $executablePath;

    /**
     * @var string|null
     */
    protected $temporaryBasePath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string      $executablePath
     * @param string|null $temporaryRootPath
     */
    public function __construct(string $executablePath, string $temporaryRootPath = null)
    {
        $this->executablePath = $executablePath;
        $this->temporaryBasePath = $temporaryRootPath;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param array         $options
     * @param \Closure|null $configure
     * @param mixed         ...$configureArguments
     *
     * @return Process
     */
    protected function createProcess(array $options = [], \Closure $configure = null, ...$configureArguments): Process
    {
        $definition = new DescribeProcess($this->executablePath);
        $definition->mergeOptions($options['process'] ?? []);

        if (null !== $configure) {
            $configure($definition, $options, ...$configureArguments);
        }

        return $definition->getInstance();
    }

    /**
     * @param BinaryInterface $binary
     *
     * @return bool
     */
    protected function isBinaryTypeJpgImage(BinaryInterface $binary): bool
    {
        return $this->isBinaryTypeMatch($binary, 'image/jpeg', 'image/jpg');
    }

    /**
     * @param BinaryInterface $binary
     *
     * @return bool
     */
    protected function isBinaryTypePngImage(BinaryInterface $binary): bool
    {
        return $this->isBinaryTypeMatch($binary, 'image/png');
    }

    /**
     * @param BinaryInterface $binary
     * @param string          ...$types
     *
     * @return bool
     */
    protected function isBinaryTypeMatch(BinaryInterface $binary, string ...$types): bool
    {
        return in_array($binary->getMimeType(), $types, true);
    }

    /**
     * @param BinaryInterface $binary
     * @param array           $options
     * @param string|null     $prefix
     *
     * @return string
     */
    protected function writeTemporaryFile(BinaryInterface $binary, array $options = [], string $prefix = null): string
    {
        $temporary = $this->acquireTemporaryFilePath($options, $prefix);

        if ($binary instanceof FileBinaryInterface) {
            $this->filesystem->copy($binary->getPath(), $temporary, true);
        } else {
            $this->filesystem->dumpFile($temporary, $binary->getContent());
        }

        return $temporary;
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
     * @param Process  $process
     * @param int[]    $okayReturnedCodes
     * @param string[] $failStdOutRegexps
     * @param bool     $requireAllRegexpsToFail
     *
     * @return bool
     */
    protected function isProcessSuccess(Process $process, array $okayReturnedCodes = [0], array $failStdOutRegexps = ['*ERROR*'], bool $requireAllRegexpsToFail = false): bool
    {
        if (0 < count($okayReturnedCodes) && false === in_array($process->getExitCode(), $okayReturnedCodes, true)) {
            return false;
        }

        $failFoundMatches = array_filter($failStdOutRegexps, function (string $search) use ($process) {
            return (new MultiplePcreMatcher($search))->isMatching($process->getOutput());
        });

        $inputLength = count($failStdOutRegexps);
        $matchLength = count($failFoundMatches);

        if (0 === $inputLength) {
            return true;
        }

        if (!$requireAllRegexpsToFail) {
            return !(0 < $matchLength);
        }

        return !($matchLength === $inputLength);
    }
}
