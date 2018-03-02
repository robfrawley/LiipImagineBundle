<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Console\Style\Helper;

use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Output\MarkupColors;
use Liip\ImagineBundle\Console\Output\MarkupOptions;
use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Console\Style\StyleOptions;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 *
 * @author Rob Frawley 2nd <rmf@src.run>
 */
trait BlockHelperTrait
{
    /**
     * @var Style
     */
    private $style;

    /**
     * @var OutputFormatterInterface
     */
    private $formatter;

    /**
     * @var BufferedOutput
     */
    private $buffer;

    /**
     * @param Style                    $style
     * @param OutputFormatterInterface $formatter
     * @param BufferedOutput           $buffer
     */
    public function __construct(Style $style, OutputFormatterInterface $formatter, BufferedOutput $buffer)
    {
        $this->style = $style;
        $this->formatter = $formatter;
        $this->buffer = $buffer;
    }

    /**
     * @param string|string[] $strings
     * @param string|null     $type
     * @param Markup|null     $markup
     * @param string|null     $prefix
     * @param int             $options
     *
     * @return self
     */
    public function block($strings, Markup $markup = null, string $type = null, string $prefix = null, int $options = Style::DEFAULT_BLOCK_MD): self
    {
        $lines = $this->createBlock(
            $strings,
            StringHelper::normalizeMarkup($markup, MarkupColors::COLOR_WHITE, MarkupColors::COLOR_BLACK),
            $type,
            $prefix,
            $options
        );

        if (!$this->style->isStyled()) {
            $lines = $this->createBlockPlainText($strings, $type, $prefix, $options);
        }

        $this->autoPrependBlock();
        $this->style->writeLines($lines)->newline();

        return $this;
    }

    /**
     * @param string|string[] $strings
     * @param string|null     $type
     * @param string|null     $char
     * @param int             $options
     *
     * @return string[]
     */
    private function createBlockPlainText($strings, string $type = null, string $char = null, int $options = 0): array
    {
        $char = static::formatChar($char, $options);
        $type = static::formatType($type, $options);

        return array_map(function (string $line): string {
            return rtrim($line);
        }, $this->compileBlockLines((array) $strings, $char, $type, $options &~ Style::PAD_WHOLE));
    }

    /**
     * @param string|string[] $strings
     * @param Markup|null     $markup
     * @param string|null     $type
     * @param string|null     $char
     * @param int             $options
     *
     * @return string[]
     */
    private function createBlock($strings, Markup $markup = null, string $type = null, string $char = null, int $options = 0): array
    {
        $char = static::formatChar($char, $options, $markup);
        $type = static::formatType($type, $options, $markup);

        return array_map(function (string $line) use ($markup, $options): string {
            return $this->markupBlockLine($line, $markup, $options);
        }, $this->compileBlockLines((array) $strings, $char, $type, $options));
    }

    /**
     * @param string[] $strings
     * @param string   $char
     * @param string   $type
     * @param int      $options
     *
     * @return string[]
     */
    private function compileBlockLines(array $strings, string $char, string $type, int $options): array
    {
        $pads = $options & self::PAD_WHOLE_X ? ' ' : '';
        $size = LineHelper::length() - $this->length($char) - ($this->length($pads) * 2);

        if ($options & Style::POS_HEADER_COLUMN) {
            $size -= $this->length($type);
        }

        if (!empty($type) && $options & Style::POS_HEADER_INLINE) {
            array_unshift($strings, StringHelper::strip($type).array_shift($strings));
        }

        $strings = StringHelper::wrap($strings, $size, $options);

        if (!empty($type) && $options & Style::POS_HEADER_INLINE) {
            $strings = array_map(function (string $string) use ($type): string {
                return preg_replace(sprintf('{^%s}', preg_quote(trim(StringHelper::strip($type)))), trim($type), $string);
            }, $strings);
        }

        if (!empty($type) && ($options & Style::POS_HEADER_BLOCK || (0 == ($options & Style::POS_HEADER_INLINE) && 0 == ($options & Style::POS_HEADER_COLUMN)))) {
            if ($options & Style::PAD_HEADER_Y) {
                array_unshift($strings, '');
            }

            array_unshift($strings, $type);
        }

        $compiled = [];

        foreach ($strings as $i => $l) {
            $c = $pads.$char;

            if (!empty($type) && $options & Style::POS_HEADER_COLUMN) {
                if (0 === $i) {
                    $c .= $type;
                } else {
                    $c .= str_repeat(' ', $this->length($type));
                }
            }

            $compiled[] = $c.$l;
        }

        if ($options & Style::PAD_WHOLE_Y) {
            array_unshift($compiled, $pads.$char);
            array_push($compiled, $pads.$char);
        }

        return $compiled;
    }

    /**
     * @param string      $string
     * @param Markup|null $markup
     * @param int         $options
     *
     * @return string
     */
    private function markupBlockLine(string $string, Markup $markup = null, int $options = 0): string
    {
        $markup = StringHelper::normalizeMarkup($markup);

        if ($options & Style::STYLE_EM_BODY) {
            $markup->addOptions(MarkupOptions::OPTION_BOLD);
        }

        if ($options & Style::STYLE_REVERSE_BODY) {
            $markup->addOptions(MarkupOptions::OPTION_REVERSE);
        }

        $rightPadding = str_repeat(' ', max(LineHelper::length() - $this->length($string), 0));

        return $markup->markup($string.$rightPadding, false, true);
    }

    /**
     * @param string $string
     *
     * @return int
     */
    private function length(string $string): int
    {
        return StringHelper::length($string, $this->formatter);
    }

    /**
     * Ensures the proper number of newlines appear prior to block writes.
     *
     * @return self
     */
    private function autoPrependBlock(): self
    {
        $previous = substr(str_replace(PHP_EOL, "\n", $this->buffer->fetch()), -2);

        $this->style->newline(isset($previous[0]) ? 2 - substr_count($previous, "\n") : 1);

        return $this;
    }

    /**
     * Ensures the proper number of newlines appear prior to normal writes.
     *
     * @return self
     */
    private function autoPrependText(): self
    {
        $this->style->newline(PHP_EOL !== substr($this->buffer->fetch(), -1) ? 1 : 0);

        return $this;
    }

    /**
     * @param string $string
     * @param array  $replacements
     *
     * @return string
     */
    private function compile(string $string, array $replacements): string
    {
        return StringHelper::compile($string, $replacements, $this->style->isStyled());
    }

    /**
     * @param string|null $char
     * @param int|null    $options
     * @param Markup|null $markup
     *
     * @return string
     */
    private static function formatChar(string $char = null, int $options = null, Markup $markup = null): string
    {
        return static::formatPart($char, $options, $markup, Style::STYLE_EM_PREFIX, Style::STYLE_REVERSE_PREFIX, function (string $char) use ($options) {
            return $char;
        });
    }

    /**
     * @param string|null $type
     * @param int|null    $options
     * @param Markup|null $markup
     *
     * @return string
     */
    private static function formatType(string $type = null, int $options = null, Markup $markup = null): string
    {
        return static::formatPart($type, $options, $markup, Style::STYLE_EM_HEADER, Style::STYLE_REVERSE_HEADER, function (string $type) use ($options) {
            return $options & Style::PAD_HEADER ? sprintf('[ %s ]', $type) : sprintf('[%s]', $type);
        });
    }

    /**
     * @param string|null $string
     * @param int|null    $options
     * @param Markup|null $markup
     * @param int         $boldFlag
     * @param int         $reverseFlag
     * @param \Closure    $formatter
     *
     * @return string
     */
    private static function formatPart(string $string = null, int $options = null, Markup $markup = null, int $boldFlag = 0, int $reverseFlag = 0, \Closure $formatter): string
    {
        if (null === $string) {
            return '';
        }

        $markup = StringHelper::normalizeMarkup($markup);

        if (null !== $boldFlag && $options & $boldFlag) {
            $markup->addOptions(MarkupOptions::OPTION_BOLD);
        }

        if (null !== $reverseFlag && $options & $reverseFlag) {
            $markup->addOptions(MarkupOptions::OPTION_REVERSE);
        }

        return $markup(StringHelper::normalize($formatter($string), $options | StyleOptions::MARKUP_STRIP)).' ';
    }
}
