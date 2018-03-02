<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Console\Output;

use Liip\ImagineBundle\Exception\InvalidArgumentException;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class Markup implements MarkupColors, MarkupOptions
{
    /**
     * @var string[]
     */
    private const ACCEPTED_COLORS = [
        self::COLOR_DEFAULT,
        self::COLOR_BLACK,
        self::COLOR_RED,
        self::COLOR_GREEN,
        self::COLOR_YELLOW,
        self::COLOR_BLUE,
        self::COLOR_MAGENTA,
        self::COLOR_CYAN,
        self::COLOR_WHITE,
    ];

    /**
     * @var string[]
     */
    private const ACCEPTED_OPTIONS = [
        self::OPTION_BOLD,
        self::OPTION_UNDERSCORE,
        self::OPTION_BLINK,
        self::OPTION_REVERSE,
    ];

    /**
     * @var string|null
     */
    private $foreground;

    /**
     * @var string|null
     */
    private $background;

    /**
     * @var string[]
     */
    private $options;

    /**
     * @param string|null $foreground
     * @param string|null $background
     * @param string[]    ...$options
     */
    public function __construct(string $foreground = null, string $background = null, string ...$options)
    {
        $this->setForeground($foreground);
        $this->setBackground($background);
        $this->setOptions(...$options);
    }

    /**
     * @param string|string[] $strings
     *
     * @return string|string[]
     */
    public function __invoke($strings)
    {
        return is_array($strings) ? array_map(function (string $line) {
            return $this->markup($line);
        }, $strings) : $this->markup($strings);
    }

    /**
     * @return string|null
     */
    public function foreground(): ?string
    {
        return $this->foreground;
    }

    /**
     * @param string|null $foreground
     *
     * @return self
     */
    public function setForeground(string $foreground = null): self
    {
        $this->foreground = $this->sanitizeColorUserInput($foreground);

        return $this;
    }

    /**
     * @return string|null
     */
    public function background(): ?string
    {
        return $this->background;
    }

    /**
     * @param string|null $background
     *
     * @return self
     */
    public function setBackground(string $background = null): self
    {
        $this->background = $this->sanitizeColorUserInput($background);

        return $this;
    }

    /**
     * @return string[]
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @param string[] ...$options
     *
     * @return Markup
     */
    public function setOptions(string ...$options): self
    {
        $this->options = $this->sanitizeOptionsUserInput(...array_unique($options));
        sort($this->options);

        return $this;
    }

    /**
     * @param string[] ...$options
     *
     * @return self
     */
    public function addOptions(string ...$options): self
    {
        return $this->setOptions(...array_merge($this->options(), $options));
    }

    /**
     * @param string $string
     * @param bool   $useExplicitColors
     * @param bool   $forceBlankMarkup
     *
     * @return string
     */
    public function markup(string $string = null, bool $useExplicitColors = false, bool $forceBlankMarkup = false): string
    {
        $string = $string ?? '';

        if (!empty($markup = $this->attributesString($useExplicitColors)) || $forceBlankMarkup) {
            return sprintf('<%s>%s</>', $markup, $string);
        }

        return $string;
    }

    /**
     * @param bool $useExplicitColors
     *
     * @return string[]
     */
    public function attributes(bool $useExplicitColors = false): array
    {
        $attributes = [];

        if (null !== $this->foreground || true === $useExplicitColors) {
            $attributes['fg'] = $this->foreground ?? 'default';
        }

        if (null !== $this->background || true === $useExplicitColors) {
            $attributes['bg'] = $this->background ?? 'default';
        }

        if (0 !== count($this->options)) {
            $attributes['options'] = implode(',', $this->options);
        }

        return $attributes;
    }

    /**
     * @param bool $useExplicitColors
     *
     * @return string
     */
    public function attributesString(bool $useExplicitColors = false): string
    {
        $attributes = $this->attributes($useExplicitColors);

        array_walk($attributes, function (string &$value, string $name) {
            $value = sprintf('%s=%s', $name, $value);
        });

        return implode(';', $attributes);
    }

    /**
     * @param string|null $color
     *
     * @return string
     */
    private function sanitizeColorUserInput(string $color = null): ?string
    {
        if (null !== $color) {
            $color = strtolower($color);

            if (!in_array($color, self::ACCEPTED_COLORS)) {
                throw new InvalidArgumentException(static::createInvalidInputExceptionMessage('color', $color, ...self::ACCEPTED_COLORS));
            }
        }

        return $color;
    }

    /**
     * @param string[] ...$options
     *
     * @return string[]
     */
    private function sanitizeOptionsUserInput(string ...$options): array
    {
        $options = array_map(function (string $option): string {
            return strtolower($option);
        }, $options);

        foreach ($options as $o) {
            if (!in_array($o, self::ACCEPTED_OPTIONS, true)) {
                throw new InvalidArgumentException(static::createInvalidInputExceptionMessage('option', $o, ...self::ACCEPTED_OPTIONS));
            }
        }

        return $options;
    }

    /**
     * @param string   $context
     * @param string   $input
     * @param string[] ...$available
     *
     * @return string
     */
    private static function createInvalidInputExceptionMessage(string $context, string $input, string ...$available): string
    {
        return sprintf('Invalid %s "%s" provided (available %ss include: %s).', $context, $input, $context, implode(', ', array_map(function (string $s): string {
            return sprintf('"%s"', $s);
        }, $available)));
    }
}