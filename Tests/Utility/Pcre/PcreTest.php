<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Utility\Pcre;

use Liip\ImagineBundle\Utility\Pcre\Pcre;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Liip\ImagineBundle\Utility\Pcre\Pcre
 */
class PcreTest extends TestCase
{
    /**
     * @return \Iterator
     */
    public static function provideToRegexData(): \Iterator
    {
        yield ['simple-match', '{^simple-match$}'];
        yield ['\.a**b*c?d??e???f????g[!a-z]h[a-z]i?(a|b)j*(c|d)k+(e|f)l', '{^\.a.+b.*c.{1}d.{2}e.{3}f.{4}g[^a-z]?h[a-z]?i(a|b)?j(c|d)*k(e|f)+l$}'];
        yield ['#[0-9]+#'];
        yield ['{^[0-9]+$}'];
    }

    /**
     * @dataProvider provideToRegexData
     *
     * @param string      $provided
     * @param string|null $expected
     */
    public function testToRegex(string $provided, string $expected = null): void
    {
        $this->assertSame($expected ?? $provided, Pcre::toRegex($provided));
    }

    /**
     * @return \Iterator
     */
    public static function provideIsRegexData(): \Iterator
    {
        yield ['simple-match', false];
        yield ['\.a**b*c?d??e???f????g[!a-z]h[a-z]i?(a|b)j*(c|d)k+(e|f)l', false];
        yield ['#[0-9]+#', true];
        yield ['{^[0-9]+$}', true];
    }

    /**
     * @dataProvider provideIsRegexData
     *
     * @param string $string
     * @param bool   $expected
     */
    public function testIsRegex(string $string, bool $expected): void
    {
        $this->assertSame($expected, Pcre::isRegex($string));
    }
}
