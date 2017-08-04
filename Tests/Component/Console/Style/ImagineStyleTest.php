<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Component\Console\IO;

use Liip\ImagineBundle\Component\Console\Style\ImagineStyle;
use Liip\ImagineBundle\Tests\AbstractTest;
use Liip\ImagineBundle\Tests\Fixtures\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * @covers \Liip\ImagineBundle\Component\Console\Style\ImagineStyle
 */
class ImagineStyleTest extends AbstractTest
{
    /**
     * @param string $format
     * @param array  $replacements
     *
     * @dataProvider provideTextData
     */
    public function testText(string $format, array $replacements)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput());
        $style->text($format, $replacements);

        $this->assertContains(vsprintf($format, $replacements), $output->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideTextData(): \Generator
    {
        yield ['Text with <em>0</em> replacements.', []];
        yield ['Text with a <comment>%s string</comment>.', ['replacement']];
        yield ['Text with <options=bold>%d %s</>, a <info>digit</info> and <info>string</info>.', [2, 'replacements']];
        yield ['%s %s (%d) <fg=red>%s</> %s %s!', ['Text', 'with', 6, 'ONLY', 'replacement', 'values']];
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @dataProvider provideTextWithoutDecorationData
     */
    public function testTextWithoutDecoration(string $format, array $replacements)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput(), false);
        $style->text($format, $replacements);

        $this->assertContains(strip_tags(vsprintf($format, $replacements)), $output->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideTextWithoutDecorationData(): \Generator
    {
        return static::provideTextData();
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @dataProvider provideLineData
     */
    public function testLine(string $format, array $replacements)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput());
        $style->line($format, $replacements);

        $this->assertContains(vsprintf($format, $replacements).PHP_EOL, $output->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideLineData(): \Generator
    {
        return static::provideTextData();
    }

    /**
     * @param int    $newlineCount
     * @param string $separator
     *
     * @dataProvider provideNewlineData
     */
    public function testNewline(int $newlineCount, string $separator)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput());
        $style->text($separator);
        $style->newline($newlineCount);
        $style->text($separator);

        $this->assertContains($separator.str_repeat(PHP_EOL, $newlineCount).$separator, $output->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideNewlineData(): \Generator
    {
        for ($i = 0; $i < 200; $i = $i + 10) {
            yield [$i, sprintf('[abcdef0123-%d]', $i)];
        }
    }

    /**
     * @param string   $character
     * @param int|null $width
     *
     * @dataProvider provideSeparatorData
     */
    public function testSeparator(string $character, int $width = null)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput());
        $style->separator($character, $width);

        $this->assertContains(str_repeat($character, $width ?: (new Terminal())->getWidth()).PHP_EOL, $output->getBuffer());
    }

    /**
     * @return \Generator
     */
    public static function provideSeparatorData(): \Generator
    {
        yield ['-', null];
        yield ['-', 100];
        yield ['-', 800];
        yield ['~-', 20];
        yield ['--- [complex-separator] ---', 2];
    }

    /**
     * @param string $title
     * @param bool   $decoration
     *
     * @dataProvider provideTitleData
     */
    public function testTitle(string $title, bool $decoration)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput(), $decoration);
        $style->title($title);


        if ($decoration) {
            $this->assertContains(sprintf('<comment>%s</>', $title).PHP_EOL, $output->getBuffer());
            $this->assertContains(sprintf('<comment>%s</>', str_repeat('=', strlen($title))).PHP_EOL, $output->getBuffer());
        } else {
            $this->assertContains(sprintf('# %s', $title).PHP_EOL, $output->getBuffer());
        }
    }

    /**
     * @return \Generator
     */
    public static function provideTitleData(): \Generator
    {
        yield ['A simple title', true];
        yield ['A simple title', false];
    }

    /**
     * @param string $format
     * @param array  $replacements
     * @param bool   $decoration
     *
     * @dataProvider provideSuccessData
     */
    public function testSuccess(string $format, array $replacements = [], bool $decoration)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput(), $decoration);
        $style->success($format, $replacements);

        $finalString = vsprintf(strip_tags($format), $replacements);

        if ($decoration) {
            $this->assertContains(sprintf('<fg=black;bg=green> [OK] %s', $finalString), $output->getBuffer());
        } else {
            $this->assertContains(sprintf('[OK] %s', $finalString).PHP_EOL, $output->getBuffer());
        }
    }

    /**
     * @return \Generator
     */
    public static function provideSuccessData(): \Generator
    {
        yield ['A <options=bold>success</> message!', [], true];
        yield ['A %s message!', ['success'], true];
        yield ['A <options=bold>success</> message!', [], false];
        yield ['A %s message!', ['success'], false];
    }

    /**
     * @param string $format
     * @param array  $replacements
     * @param bool   $decoration
     *
     * @dataProvider provideErrorData
     */
    public function testError(string $format, array $replacements = [], bool $decoration)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput(), $decoration);
        $style->error($format, $replacements);

        $finalString = vsprintf(strip_tags($format), $replacements);

        if ($decoration) {
            $this->assertContains(sprintf('<fg=white;bg=red> [ERROR] %s', $finalString), $output->getBuffer());
        } else {
            $this->assertContains(sprintf('[ERROR] %s', $finalString).PHP_EOL, $output->getBuffer());
        }
    }

    /**
     * @return \Generator
     */
    public static function provideErrorData(): \Generator
    {
        yield ['An <options=bold>error</> message!', [], true];
        yield ['An %s message!', ['error'], true];
        yield ['An <options=bold>error</> message!', [], false];
        yield ['An %s message!', ['error'], false];
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @dataProvider provideInvalidFormatAndReplacementsData
     *
     * @expectedException \Liip\ImagineBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp {Invalid string format "[^"]+" or replacements "[^"]+".}
     */
    public function testInvalidFormatAndReplacements(string $format, array $replacements)
    {
        $style = $this->createImagineStyle($output = $this->createBufferedOutput());
        $style->text($format, $replacements);
    }

    /**
     * @return \Generator
     */
    public static function provideInvalidFormatAndReplacementsData(): \Generator
    {
        yield ['%s %s', ['bad-replacements-array']];
        yield ['%s %s %s %s %s', ['not', 'enough', 'replacements']];
        yield ['%s %d %s', ['missing', 1]];
    }

    /**
     * @param OutputInterface $output
     * @param bool            $decoration
     *
     * @return ImagineStyle
     */
    private function createImagineStyle(OutputInterface $output, bool $decoration = true): ImagineStyle
    {
        return new ImagineStyle(new ArrayInput([]), $output, $decoration);
    }

    /**
     * @return BufferedOutput
     */
    private function createBufferedOutput(): BufferedOutput
    {
        return new BufferedOutput();
    }
}
