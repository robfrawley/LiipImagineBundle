<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Process;

use Symfony\Component\Process\Process;

final class DescribeProcess
{
    /**
     * @var string
     */
    private $executable;

    /**
     * @var string[]
     */
    private $arguments = [];

    /**
     * @var mixed[]
     */
    private $options = [];

    /**
     * @param string $executable
     */
    public function __construct(string $executable)
    {
        $this->executable = $executable;
    }

    /**
     * @param string $argument
     * @param mixed  ...$replacements
     *
     * @return self
     */
    public function pushArgument(string $argument, ...$replacements): self
    {
        $this->arguments[] = 0 === count($replacements) ? $argument : vsprintf($argument, $replacements);

        return $this;
    }

    /**
     * @param mixed[] $options
     *
     * @return self
     */
    public function mergeOptions(array $options): self
    {
        $this->options += $options;

        return $this;
    }

    /**
     * @param array $arguments
     * @param array $options
     *
     * @return Process
     */
    public function getInstance(): Process
    {
        $process = new Process(array_merge([$this->executable], $this->arguments));

        if (!empty($this->options['timeout'] ?? '')) {
            $process->setTimeout($this->options['timeout']);
        }

        if (!empty($this->options['idle_timeout'] ?? '')) {
            $process->setIdleTimeout($this->options['idle_timeout']);
        }

        if (isset($this->options['working_directory'])) {
            $process->setWorkingDirectory($this->options['working_directory']);
        }

        if (is_array($this->options['environment_variables'] ?? null)) {
            $process->setEnv($this->options['environment_variables']);
        }

        if (true === ($this->options['use_pty'] ?? false)) {
            $process->setPty(true);
        }

        if (true === ($this->options['use_tty'] ?? false)) {
            $process->setTty(true);
        }

        return $process;
    }
}
