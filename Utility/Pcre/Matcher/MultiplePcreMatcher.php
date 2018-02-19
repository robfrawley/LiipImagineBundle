<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Pcre\Matcher;

use Liip\ImagineBundle\Utility\Pcre\Pcre;

final class MultiplePcreMatcher
{
    /**
     * @var string[]
     */
    private $regexps;

    /**
     * @param string ...$patterns
     */
    public function __construct(string ...$patterns)
    {
        $this->regexps = array_map(function (string $pattern) {
            return Pcre::toRegex($pattern);
        }, $patterns);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function isMatching(string $string): bool
    {
        foreach ($this->regexps as $regex) {
            if (1 !== preg_match($regex, $string)) {
                return false;
            }
        }

        return true;
    }
}
