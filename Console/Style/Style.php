<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Console\Style;

use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Output\MarkupOptions;
use Liip\ImagineBundle\Console\Style\Helper\BlockHelper;
use Liip\ImagineBundle\Console\Style\Helper\LineHelper;
use Liip\ImagineBundle\Console\Style\Helper\StringHelper;
use Liip\ImagineBundle\Console\Style\Helper\TitleHelper;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class Style implements StyleOptions
{
    /**
     * @var bool
     */
    private $styled;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var BufferedOutput
     */
    private $buffer;

    /**
     * @var BlockHelper
     */
    private $blocks;

    /**
     * @var TitleHelper
     */
    private $titles;

    /**
     * @param OutputInterface $output
     * @param bool            $styled
     */
    public function __construct(OutputInterface $output, bool $styled = true)
    {
        $this->setStyled($styled);

        $this->output = $output;
        $this->buffer = new BufferedOutput($output->getVerbosity(), false, clone $output->getFormatter());
        $this->blocks = new BlockHelper($this, $output->getFormatter(), $this->buffer);
        $this->titles = new TitleHelper($this, $output->getFormatter(), $this->buffer);
    }

    /**
     * @param bool $styled
     *
     * @return Style
     */
    public function setStyled(bool $styled): self
    {
        $this->styled = $styled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isStyled(): bool
    {
        return $this->styled;
    }

    /**
     * @param string|string[] $strings
     * @param bool            $newline
     *
     * @return self
     */
    public function write($strings = [], $newline = false): self
    {
        $this->output->write($strings = $strings ?? '', $newline, OutputInterface::OUTPUT_NORMAL);
        $this->buffer->write($this->reduceBuffer($strings), $newline, OutputInterface::OUTPUT_NORMAL);

        return $this;
    }

    /**
     * @param string|string[]
     *
     * @return self
     */
    public function writeLines($lines = []): self
    {
        return $this->write($lines, true);
    }

    /**
     * @param string|null $text
     * @param mixed[]     ...$replacements
     *
     * @return self
     */
    public function text(string $text = null, ...$replacements): self
    {
        return $this->write(StringHelper::compile($text ?? '', $replacements, $this->styled));
    }

    /**
     * @param string|null $line
     * @param mixed[]     ...$replacements
     *
     * @return self
     */
    public function line(string $line = null, ...$replacements): self
    {
        return $this->writeLines(StringHelper::compile($line ?? '', $replacements, $this->styled));
    }

    /**
     * @param int $count
     *
     * @return self
     */
    public function newline(int $count = 1): self
    {
        return $this->text(str_repeat(PHP_EOL, $count));
    }

    /**
     * @param int $count
     *
     * @return self
     */
    public function space(int $count = 1): self
    {
        return $this->text(str_repeat(' ', $count));
    }

    /**
     * @param string|null $character
     * @param int|null    $width
     * @param Markup|null $decorator
     *
     * @return self
     */
    public function separator(string $character = null, int $width = null, Markup $decorator = null): self
    {
        return $this->text(StringHelper::normalizeMarkup($decorator)(
            str_repeat($character ?? '-', $width ?? LineHelper::length())
        ))->newline();
    }

    /**
     * @param string      $status
     * @param Markup|null $markup
     *
     * @return self
     */
    public function status(string $status, Markup $markup = null): self
    {
        $markup = StringHelper::normalizeMarkup($markup);
        $bolded = StringHelper::normalizeMarkup($markup)->setOptions(MarkupOptions::OPTION_BOLD);

        return $this->text($markup('(').$bolded($status).$markup(')'));
    }


    /**
     * @param string      $item
     * @param string      $group
     * @param Markup|null $markup
     *
     * @return self
     */
    public function group(string $item, string $group, Markup $markup = null): self
    {
        $markup = StringHelper::normalizeMarkup($markup);
        $bolded = StringHelper::normalizeMarkup($markup)->setOptions(MarkupOptions::OPTION_BOLD);

        return $this->text($bolded(sprintf('%s[', $item)).$markup($group).$bolded(']'));
    }

    /**
     * @return TitleHelper
     */
    public function titles(): TitleHelper
    {
        return $this->titles;
    }

    /**
     * @return BlockHelper
     */
    public function blocks(): BlockHelper
    {
        return $this->blocks;
    }

    /**
     * @param string|string[] $strings
     *
     * @return string[]
     */
    private function reduceBuffer($strings): array
    {
        return array_map(function ($l) {
            return substr($l, -4);
        }, array_merge([$this->buffer->fetch()], (array) $strings));
    }
}
