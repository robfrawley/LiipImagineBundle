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
use Liip\ImagineBundle\Console\Style\StyleOptions;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class StringHelper implements StyleOptions
{
    /**
     * @param string               $string
     * @param OutputFormatter|null $formatter
     *
     * @return int
     */
    public static function length(string $string, OutputFormatter $formatter = null): int
    {
        return $formatter ? Helper::strlenWithoutDecoration($formatter, $string) : Helper::strlen($string);
    }

    /**
     * @param string|string[] $strings
     * @param int|null        $width
     * @param int             $options
     *
     * @return string[]
     */
    public static function wrap($strings, int $width = null, int $options = 0): array
    {
        $width = $width ?? LineHelper::length();
        $lines = [];
        $input = static::normalize(array_map(function (string $s) {
            return rtrim($s);
        }, (array) $strings), $options);

        if ($options & self::STYLE_INDENT_PARAGRAPHS) {
            $input = array_map(function (string $line): string {
                return sprintf('  %s', $line);
            }, $input);
            $input[0] = substr($input[0], 2);
        }

        foreach ($input as $index => $value) {
            $lines = array_merge($lines, explode(PHP_EOL, wordwrap($value, $width, PHP_EOL, true)));

            if (1 < count($strings) && $index < count($strings) - 1 && $options & self::PAD_PARAGRAPHS) {
                array_push($lines, '');
            }
        }

        return $lines;
    }

    /**
     * @param string|string[] $strings
     * @param int             $options
     *
     * @return string|string[]
     */
    public static function normalize($strings, int $options = 0)
    {
        if ($options & self::MARKUP_STRIP) {
            $strings = static::strip($strings);
        }

        if ($options & self::MARKUP_ESCAPE) {
            $strings = static::escape($strings);
        }

        return $strings;
    }

    /**
     * @param string|string[] $strings
     *
     * @return string|string[]
     */
    public static function escape($strings)
    {
        if (!is_array($strings)) {
            return OutputFormatter::escape($strings);
        }

        return array_map(function (string $s) {
            return OutputFormatter::escape($s);
        }, (array) $strings);
    }

    /**
     * @param string|string[] $strings
     *
     * @return string|string[]
     */
    public static function strip($strings)
    {
        if (!is_array($strings)) {
            return strip_tags($strings);
        }

        return array_map(function (string $s) {
            return strip_tags($s);
        }, (array) $strings);
    }

    /**
     * @param string $format
     * @param array  $replacements
     * @param bool   $styled
     *
     * @return string
     */
    public static function compile(string $format, array $replacements = [], bool $styled = true): string
    {
        if (!$styled) {
            $format = StringHelper::normalize($format, self::MARKUP_STRIP | self::MARKUP_ESCAPE);
        }

        if (0 === count($replacements)) {
            return $format;
        }

        if (false !== $compiled = @vsprintf($format, $replacements)) {
            return $compiled;
        }

        throw new InvalidArgumentException(sprintf('Invalid string format "%s" or replacement values "%s".', $format, static::exportReplacements($replacements)));
    }

    /**
     * @param Markup|null $markup
     * @param string|null $defaultForeground
     * @param string|null $defaultBackground
     * @param string[]    ...$defaultOptions
     *
     * @return Markup
     */
    public static function normalizeMarkup(Markup $markup = null, string $defaultForeground = null, string $defaultBackground = null, string ...$defaultOptions): Markup
    {
        return $markup ? clone $markup : new Markup($defaultForeground, $defaultBackground, ...$defaultOptions);
    }

    /**
     * @param array $replacements
     *
     * @return string
     */
    private static function exportReplacements(array $replacements): string
    {
        return implode(', ', array_map(function ($replacement): string {
            return var_export($replacement, true);
        }, $replacements));
    }
}
