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
use Liip\ImagineBundle\Exception\Imagine\Filter\PostProcessor\InvalidOptionException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

abstract class AbstractPostProcessor implements PostProcessorInterface, ConfigurablePostProcessorInterface
{
    /**
     * @var string
     */
    protected $executablePath;

    /**
     * @var string|null
     */
    protected $temporaryRootPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string      $executablePath
     * @param string|null $temporaryRootPath
     */
    public function __construct($executablePath, $temporaryRootPath = null)
    {
        $this->executablePath = $executablePath;
        $this->temporaryRootPath = $temporaryRootPath;
        $this->filesystem = new Filesystem();
    }

    /**
     * Performs post-process operation on passed binary and returns the resulting binary.
     *
     * @param BinaryInterface $binary
     * @param array           $options
     *
     * @throws ProcessFailedException
     *
     * @return BinaryInterface
     */
    public function process(BinaryInterface $binary /* , array $options = array() */)
    {
        return $this->doProcess($binary, func_num_args() >= 2 ? func_get_arg(1) : array());
    }

    /**
     * Performs post-process operation on passed binary and returns the resulting binary.
     *
     * @deprecated This method was deprecated in 1.10.0 and will be removed in 2.0. Instead, the signature of the
     *             PostProcessorInterface::process() method has been expanded to include an options array.
     *
     * @param BinaryInterface $binary
     * @param array           $options
     *
     * @throws ProcessFailedException
     *
     * @return BinaryInterface
     */
    public function processWithConfiguration(BinaryInterface $binary, array $options)
    {
        @trigger_error(sprintf('The %s() method was deprecated in 1.10.0 and will be removed in 2.0 in favor of '.
            'the %s::process() method, which was expanded to allow passing options as the second argument.',
            __FUNCTION__, get_called_class()), E_USER_DEPRECATED);

        return $this->doProcess($binary, $options);
    }

    /**
     * @param BinaryInterface $binary
     * @param array           $options
     *
     * @throws ProcessFailedException
     *
     * @return BinaryInterface
     */
    abstract protected function doProcess(BinaryInterface $binary, array $options);

    /**
     * @param array $options
     * @param array $arguments
     *
     * @return ProcessBuilder
     */
    protected function createProcessBuilder(array $options = array(), array $arguments = array())
    {
        $builder = new ProcessBuilder($arguments ?: array($this->executablePath));

        $this->configureProcessBuilder($builder, $options);

        return $builder;
    }

    /**
     * @param BinaryInterface $binary
     *
     * @return bool
     */
    protected function isBinaryJpgMimeType(BinaryInterface $binary)
    {
        return $this->isBinaryMatchingMimeType($binary, array('image/jpeg', 'image/jpg'));
    }

    /**
     * @param BinaryInterface $binary
     *
     * @return bool
     */
    protected function isBinaryPngMimeType(BinaryInterface $binary)
    {
        return $this->isBinaryMatchingMimeType($binary, array('image/png'));
    }

    /**
     * @param BinaryInterface $binary
     * @param string[]        $types
     *
     * @return bool
     */
    protected function isBinaryMatchingMimeType(BinaryInterface $binary, array $types)
    {
        return in_array($binary->getMimeType(), $types);
    }

    /**
     * @param BinaryInterface $binary
     * @param array           $options
     * @param null            $prefix
     *
     * @return string
     */
    protected function writeTemporaryFile(BinaryInterface $binary, array $options = array(), $prefix = null)
    {
        $file = $this->acquireTemporaryFilePath($options, $prefix);

        if ($binary instanceof FileBinaryInterface) {
            $this->filesystem->copy($binary->getPath(), $file, true);
        } else {
            $this->filesystem->dumpFile($file, $binary->getContent());
        }

        return $file;
    }

    /**
     * @param array  $options
     * @param string $prefix
     *
     * @return string
     */
    protected function acquireTemporaryFilePath(array $options, $prefix = null)
    {
        $root = isset($options['temp_dir']) ? $options['temp_dir'] : ($this->temporaryRootPath ?: sys_get_temp_dir());

        if (!is_dir($root)) {
            try {
                $this->filesystem->mkdir($root);
            } catch (IOException $exception) {
                // ignore exceptions as tempnam func will revert to system default temporary path if required
            }
        }

        if (false === $file = @tempnam($root, $prefix ?: 'post-processor')) {
            throw new \RuntimeException(sprintf('Temporary file cannot be created in "%s"', $root));
        }

        return $file;
    }

    /**
     * @param Process $process
     * @param array   $validReturns
     * @param array   $errorStrings
     *
     * @return bool
     */
    protected function isProcessSuccessful(Process $process, array $validReturns = array(0), array $errorStrings = array('ERROR'))
    {
        if (count($validReturns) > 0 && !in_array($process->getExitCode(), $validReturns)) {
            return false;
        }

        foreach ($errorStrings as $string) {
            if (false !== strpos($process->getOutput(), $string)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $method
     */
    protected function triggerSetterMethodDeprecation($method)
    {
        @trigger_error(sprintf('The %s() method was deprecated in 1.10.0 and will be removed in 2.0. You must '.
            'setup the class state via its __construct() method. You can still pass filter-specific options to the '.
            'process() method to overwrite behavior.', $method), E_USER_DEPRECATED);
    }

    /**
     * @param ProcessBuilder $builder
     * @param array          $options
     */
    private function configureProcessBuilder(ProcessBuilder $builder, array $options)
    {
        if (!isset($options['process'])) {
            return;
        }

        if (isset($options['process']['environment_variables'])) {
            if (!is_array($options['process']['environment_variables'])) {
                throw new InvalidOptionException('the "process:environment_variables" option must be an array of name => value pairs', $options);
            }

            foreach ($options['process']['environment_variables'] as $name => $value) {
                $builder->setEnv($name, $value);
            }
        }

        if (isset($options['process']['options'])) {
            if (!is_array($options['process']['options'])) {
                throw new InvalidOptionException('the "process:options" option must be an array of options intended for the proc_open function call', $options);
            }

            foreach ($options['process']['options'] as $name => $value) {
                $builder->setOption($name, $value);
            }
        }

        if (isset($options['process']['timeout'])) {
            $builder->setTimeout($options['process']['timeout']);
        }

        if (isset($options['process']['prefix'])) {
            $builder->setPrefix($options['process']['prefix']);
        }

        if (isset($options['process']['working_directory'])) {
            $builder->setWorkingDirectory($options['process']['working_directory']);
        }
    }
}
