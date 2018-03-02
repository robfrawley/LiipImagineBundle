<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Console\Style;

use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Output\MarkupColors;
use Liip\ImagineBundle\Console\Style\Helper\LineHelper;
use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Tests\AbstractTest;
use Liip\ImagineBundle\Tests\Console\Output\BufferedOutput;
use Liip\ImagineBundle\Tests\Fixtures\Console\FixtureInstruction;
use Liip\ImagineBundle\Tests\Fixtures\Console\FixtureProvider;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Liip\ImagineBundle\Console\Style\Helper\BlockHelper
 * @covers \Liip\ImagineBundle\Console\Style\Helper\BlockHelperTrait
 * @covers \Liip\ImagineBundle\Console\Style\Helper\LineHelper
 * @covers \Liip\ImagineBundle\Console\Style\Helper\StringHelper
 * @covers \Liip\ImagineBundle\Console\Style\Helper\TitleHelper
 * @covers \Liip\ImagineBundle\Console\Style\Style
 */
class ImagineStyleTest extends AbstractTest
{
    public function setUp()
    {
        parent::setUp();
        putenv('COLUMNS=120');
    }

    /**
     * @return \Iterator
     */
    public static function provideInstructionData(): \Iterator
    {
        $data = new FixtureProvider();

        foreach ($data->allInstructions() as $instruction) {
            yield [$instruction];
        }
    }

    /**
     * @dataProvider provideInstructionData
     *
     * @param FixtureInstruction $instruction
     */
    public function testInstructions(FixtureInstruction $instruction): void
    {
        $instruction->provider()($this->createImagineStyle($o = $this->createBufferedOutput()), $instruction->fixture());

        $this->assertSame($instruction->expected(), $o->getBuffer(), vsprintf('Failed asserting that instruction "%s-%002d" provider (%s) matches expected output (%s).', [
            $instruction->ns(),
            $instruction->index(),
            $instruction->providerFilePath(),
            $instruction->expectedFilePath(),
        ]));
    }

    /**
     * @return \Generator
     */
    public static function provideTextData(): \Generator
    {
        yield ['Text with <em>0</em> replacements.'];
        yield ['Text with a <comment>%s string</comment>.', 'replacement'];
        yield ['Text with <options=bold>%d %s</>, a <info>digit</info> and <info>string</info>.', 2, 'replacements'];
        yield ['%s %s (%d) <fg=red>%s</> %s %s!', 'Text', 'with', 6, 'ONLY', 'replacement', 'values'];
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @dataProvider provideTextData
     */
    public function testText(string $format, ...$replacements)
    {
        $this->createImagineStyle($o = $this->createBufferedOutput())
            ->text($format, ...$replacements);

        $this->assertContains(vsprintf($format, $replacements), $o->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideTextWithoutDecorationData(): \Generator
    {
        return static::provideTextData();
    }

    /**
     * @dataProvider provideTextWithoutDecorationData
     *
     * @param string $format
     * @param array  ...$replacements
     */
    public function testTextWithoutDecoration(string $format, ...$replacements)
    {
        $this->createImagineStyle($o = $this->createBufferedOutput(), false)
            ->text($format, ...$replacements);

        $this->assertContains(strip_tags(vsprintf($format, $replacements)), $o->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideLineData(): \Generator
    {
        return static::provideTextData();
    }

    /**
     * @dataProvider provideLineData
     *
     * @param string $format
     * @param array  ...$replacements
     */
    public function testLine(string $format, ...$replacements)
    {
        $this->createImagineStyle($o = $this->createBufferedOutput())
            ->line($format, ...$replacements);

        $this->assertContains(vsprintf($format, $replacements).PHP_EOL, $o->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideNewlineData(): \Generator
    {
        for ($i = 0; $i <= 300; $i += mt_rand(40, 60)) {
            yield [$i, sprintf('[abcdef0123-%d]', $i)];
        }
    }

    /**
     * @dataProvider provideNewlineData
     *
     * @param int    $newlines
     * @param string $separator
     */
    public function testNewline(int $newlines, string $separator)
    {
        $this->createImagineStyle($o = $this->createBufferedOutput())
            ->text($separator)
            ->newline($newlines)
            ->text($separator);

        $this->assertContains(sprintf('%1$s%2$s%1$s', $separator, str_repeat(PHP_EOL, $newlines)), $o->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideSpaceData(): \Generator
    {
        yield [0];

        for ($spaces = 1; $spaces < 400; $spaces *= 2) {
            yield [$spaces];
        }
    }

    /**
     * @dataProvider provideSpaceData
     *
     * @param int $count
     */
    public function testSpace(int $count)
    {
        $this->createImagineStyle($o = $this->createBufferedOutput())
            ->space($count);

        if (0 === $count) {
            $this->assertEmpty($o->getBuffer());
        } else {
            $this->assertContains(str_repeat(' ', $count), $o->getBuffer());
        }
    }

    /**
     * @return \Generator
     */
    public static function provideInvalidReplacementsData(): \Generator
    {
        yield ['%s %s', 'bad-replacements-array'];
        yield ['%s %s %s %s %s', 'not', 'enough', 'replacements'];
        yield ['%s %d %s', 'missing', 1];
    }

    /**
     * @dataProvider provideInvalidReplacementsData
     *
     * @param string $format
     * @param array  ...$replacements
     */
    public function testInvalidReplacements(string $format, ...$replacements)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('{Invalid string format "[^"]+" or replacement values "[^"]+".}');

        $style = $this->createImagineStyle($output = $this->createBufferedOutput());
        $style->text($format, ...$replacements);
    }

    /**
     * @return \Generator
     */
    public static function provideSeparatorData(): \Generator
    {
        $strings = ['-', '--{{ <a-complex-separator> }}--'];
        $lengths = [null, 1, 50, 100, 200];

        foreach ($lengths as $l) {
            foreach ($strings as $s) {
                yield [$s, $l, static::getMarkup()];
            }
        }
    }

    /**
     * @dataProvider provideSeparatorData
     *
     * @param string   $character
     * @param int|null $width
     * @param Markup   $decoration
     */
    public function testSeparator(string $character, int $width = null, Markup $decoration)
    {
        $this
            ->createImagineStyle($o = $this->createBufferedOutput())
            ->separator($character, $width, $decoration);

        $this->assertContains(
            $decoration(str_repeat($character, $width ?? LineHelper::length())), $o->getBuffer()
        );
    }

    /**
     * @param OutputInterface $output
     * @param bool            $styles
     *
     * @return Style
     */
    private function createImagineStyle(OutputInterface $output, bool $styles = true): Style
    {
        return new Style($output, $styles);
    }

    /**
     * @return BufferedOutput
     */
    private function createBufferedOutput(): BufferedOutput
    {
        return new BufferedOutput();
    }

    /**
     * @return array
     */
    private static function getConsoleColors(): array
    {
        return [
            null,
            MarkupColors::COLOR_BLACK,
            MarkupColors::COLOR_RED,
            MarkupColors::COLOR_GREEN,
            MarkupColors::COLOR_YELLOW,
            MarkupColors::COLOR_BLACK,
            MarkupColors::COLOR_MAGENTA,
            MarkupColors::COLOR_CYAN,
            MarkupColors::COLOR_WHITE
        ];
    }

    /**
     * @return string|null
     */
    private static function getRandomConsoleColor(): ?string
    {
        $colors = static::getConsoleColors();
        shuffle($colors);

        return array_pop($colors);
    }

    /**
     * @return Markup[]
     */
    private static function getMarkupRandom(): array
    {
        return array_map(function(string $color = null) {
            return new Markup($color, static::getRandomConsoleColor());
        }, static::getConsoleColors());
    }

    /**
     * @param string|null $foreground
     * @param string|null $background
     * @param string[]    ...$options
     *
     * @return Markup
     */
    private static function getMarkup(string $foreground = null, string $background = null, string ...$options): Markup
    {
        return new Markup($foreground ?? static::getRandomConsoleColor(), $background ?? static::getRandomConsoleColor(), ...$options);
    }
}