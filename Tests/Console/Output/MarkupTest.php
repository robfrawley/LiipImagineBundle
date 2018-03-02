<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Console\Output;

use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Output\MarkupColors;
use Liip\ImagineBundle\Console\Output\MarkupOptions;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Console\Output\Markup
 */
class MarkupTest extends AbstractTest
{
    /**
     * @return \Iterator
     */
    public static function provideMarkupData(): \Iterator
    {
        $input = 'a simple string';

        yield ['', $input, null, null, []];
        yield ['<fg=default;bg=default>', $input, null, null, [], true];
        yield ['<fg=white>', $input, MarkupColors::COLOR_WHITE, null, []];
        yield ['<fg=white;bg=default>', $input, MarkupColors::COLOR_WHITE, null, [], true];
        yield ['<bg=white>', $input, null, MarkupColors::COLOR_WHITE, []];
        yield ['<fg=default;bg=white>', $input, null, MarkupColors::COLOR_WHITE, [], true];
        yield ['<fg=white;bg=magenta>', $input, MarkupColors::COLOR_WHITE, MarkupColors::COLOR_MAGENTA, []];
        yield ['<fg=white;bg=magenta>', $input, MarkupColors::COLOR_WHITE, MarkupColors::COLOR_MAGENTA, [], true];
        yield ['<options=bold>', $input, null, null, [MarkupOptions::OPTION_BOLD]];
        yield ['<fg=default;bg=default;options=bold>', $input, null, null, [MarkupOptions::OPTION_BOLD], true];
        yield ['<options=bold,underscore>', $input, null, null, [MarkupOptions::OPTION_BOLD, MarkupOptions::OPTION_UNDERSCORE]];
        yield ['<fg=blue;options=bold,underscore>', $input, MarkupColors::COLOR_BLUE, null, [MarkupOptions::OPTION_BOLD, MarkupOptions::OPTION_UNDERSCORE]];
        yield ['<bg=blue;options=bold,underscore>', $input, null, MarkupColors::COLOR_BLUE, [MarkupOptions::OPTION_BOLD, MarkupOptions::OPTION_UNDERSCORE]];
    }

    /**
     * @dataProvider provideMarkupData
     *
     * @param string      $expected
     * @param string      $string
     * @param string|null $fg
     * @param string|null $bg
     * @param array       $options
     * @param bool        $explicit
     */
    public function testMarkup(string $expected, string $string = '', string $fg = null, string $bg = null, array $options, bool $explicit = false): void
    {
        $compiled = sprintf('%s%s%s', $expected, $string, $expected ? '</>' : '');
        $markup = new Markup($fg, $bg, ...$options);

        $this->assertStringStartsWith($compiled, $markup->markup($string, $explicit));

        if (null !== $fg && null !== $bg) {
            foreach ($markup([$string, $string]) as $line) {
                $this->assertStringStartsWith($compiled, $line);
            }
        }

        if (false === $explicit) {
            $this->assertStringStartsWith($compiled, $markup($string));
        }

        if (null !== $fg) {
            $this->assertSame($fg, $markup->foreground());
        }

        if (null !== $bg) {
            $this->assertSame($bg, $markup->background());
        }

        sort($options);

        $this->assertSame($options, $markup->options());
        $markup->addOptions(MarkupOptions::OPTION_BLINK);

        $options = array_merge($options, [MarkupOptions::OPTION_BLINK]);
        sort($options);
        $this->assertSame($options, $markup->options());
    }

    /**
     * @return \Iterator
     */
    public static function provideInvalidColorData(): \Iterator
    {
        yield ['amber'];
        yield ['aqua'];
        yield ['azure'];
        yield ['blond'];
        yield ['brass'];
        yield ['emerald'];
    }

    /**
     * @dataProvider provideInvalidColorData
     *
     * @param string $color
     */
    public function testInvalidForegroundColor(string $color): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('{Invalid color "[^"]+" provided \(available colors include: .+\)\.}');

        new Markup($color, MarkupColors::COLOR_DEFAULT);
    }

    /**
     * @dataProvider provideInvalidColorData
     *
     * @param string $color
     */
    public function testInvalidBackgroundColor(string $color): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('{Invalid color "[^"]+" provided \(available colors include: .+\)\.}');

        new Markup(MarkupColors::COLOR_DEFAULT, $color);
    }

    /**
     * @return \Iterator
     */
    public static function provideInvalidOptionsData(): \Iterator
    {
        yield ['underline'];
        yield ['emphasis'];
        yield [MarkupOptions::OPTION_BOLD, 'underline'];
        yield [MarkupOptions::OPTION_BOLD, 'emphasis'];
    }

    /**
     * @dataProvider provideInvalidOptionsData
     *
     * @param string[] ...$options
     */
    public function testInvalidOptions(string ...$options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('{Invalid option "[^"]+" provided \(available options include: .+\)\.}');

        new Markup(MarkupColors::COLOR_DEFAULT, MarkupColors::COLOR_DEFAULT, ...$options);
    }
}