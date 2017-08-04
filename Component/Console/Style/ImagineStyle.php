<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Component\Console\Style;

use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

class ImagineStyle
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var bool
     */
    private $decoration;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param bool            $decoration
     */
    public function __construct(InputInterface $input, OutputInterface $output, bool $decoration = true)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->decoration = $decoration;
    }

    /**
     * {@inheritdoc}
     */
    public function text(string $format, array $replacements = []): ImagineStyle
    {
        $this->io->write($this->compileString($format, $replacements));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function line(string $format, array $replacements = []): ImagineStyle
    {
        $this->io->writeln($this->compileString($format, $replacements));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function newline(int $count = 1): ImagineStyle
    {
        $this->io->newLine($count);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function separator(string $character = '-', int $width = null): ImagineStyle
    {
        $this->line(str_repeat($character, $width ?: (new Terminal())->getWidth()));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function title(string $title): ImagineStyle
    {
        if ($this->decoration) {
            $this->io->title($title);
        } else {
            $this->io->newLine();
            $this->io->writeln(sprintf('# %s', $title));
            $this->io->newLine();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function success(string $format, array $replacements = []): ImagineStyle
    {
        $string = $this->compileString(strip_tags($format), $replacements);

        if ($this->decoration) {
            $this->io->success($string);
        } else {
            $this->io->newLine();
            $this->io->writeln(sprintf('[OK] %s', $string));
            $this->io->newLine();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $format, array $replacements = []): ImagineStyle
    {
        $string = $this->compileString(strip_tags($format), $replacements);

        if ($this->decoration) {
            $this->io->error($string);
        } else {
            $this->io->newLine();
            $this->io->writeln(sprintf('[ERROR] %s', $string));
            $this->io->newLine();
        }

        return $this;
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @return string
     */
    private function compileString(string $format, array $replacements = []): string
    {
        if (!$this->decoration) {
            $format = strip_tags($format);
        }

        if (0 === count($replacements)) {
            return $format;
        }

        if (false !== $compiled = @vsprintf($format, $replacements)) {
            return $compiled;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid string format "%s" or replacements "%s".', $format, implode(', ', array_map(function ($replacement) {
                return var_export($replacement, true);
            }, $replacements)))
        );
    }
}
