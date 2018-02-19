<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Utility\Pcre\Matcher;

use Liip\ImagineBundle\Utility\Pcre\Matcher\MultiplePcreMatcher;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Liip\ImagineBundle\Utility\Pcre\Matcher\MultiplePcreMatcher
 */
class MultiplePcreMatcherTest extends TestCase
{
    /**
     * @return \Iterator
     */
    public static function provideIsMatchingData(): \Iterator
    {
        yield ['simple', true, 'simple'];
        yield ['simple-not-match', false, 'simple'];
        yield ['simple-not-match', false, 'simple-not-match', 'simple'];
        yield ['glob', true, 'glob', 'g*', 'gl??', 'g[k-m]o[a-z]'];
        yield ['glob-is-match', true, 'glob-*'];
        yield ['glob-is-match', true, '*-*-*', 'glob-??-[a-z]**'];
        yield ['glob-not-match', false, '*-*-*', 'glob-??-[a-z]**', ''];
    }

    /**
     * @dataProvider provideIsMatchingData
     *
     * @param string $provided
     * @param bool   $expected
     * @param string ...$patterns
     */
    public function testIsMatching(string $provided, bool $expected, string ...$patterns): void
    {
        $this->assertSame($expected, (new MultiplePcreMatcher(...$patterns))->isMatching($provided));
    }
}
