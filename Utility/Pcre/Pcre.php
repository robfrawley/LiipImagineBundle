<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Pcre;

final class Pcre
{
    /**
     * @param string $string
     *
     * @return string
     */
    public static function toRegex(string $string): string
    {
        return self::isRegex($string)? $string : self::globToRegex($string);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isRegex($string)
    {
        if (preg_match('/^(.{3,}?)[imsxuADU]*$/', $string, $m)) {
            $s = substr($m[1], 0, 1);
            $e = substr($m[1], -1);

            if ($s === $e) {
                return !preg_match('/[*?[:alnum:] \\\\]/', $s);
            }

            foreach ([['{', '}'], ['(', ')'], ['[', ']'], ['<', '>']] as $delimiters) {
                if ($s === $delimiters[0] && $e === $delimiters[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function globToRegex(string $string): string
    {
        self::subStringReplaceMatches('{(\?+)(?!\(.+?\))}', function (string $match) {
            return sprintf('.{%d}', strlen($match));
        }, $string);

        self::subStringReplaceMatches('{(\[!?.+?\])}', function (string $match) {
            return '!' === $match[1] ? sprintf('[^%s]?', substr($match, 2, -1)) :
                sprintf('[%s]?', substr($match, 1, -1));
        }, $string);

        self::subStringReplaceMatches('{(\*{1,2}(?!\(.+?\)))}', function (string $match) {
            return 1 === strlen($match) ? '.*' : '.+';
        }, $string);

        self::subStringReplaceMatches('{([?*+]\(.+?\))}', function (string $match) {
            return sprintf('(%s)%s', substr($match, 2, -1), substr($match, 0, 1));
        }, $string);

        return sprintf('{^%s$}', $string);
    }

    /**
     * @param string   $search
     * @param \Closure $generateReplacement
     * @param string   $string
     */
    private static function subStringReplaceMatches(string $search, \Closure $generateReplacement, string &$string): void
    {
        if (preg_match_all($search, $string, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = 0;

            foreach ($matches[0] as list($match, $index)) {
                $length = strlen($match);
                $regexp = $generateReplacement($match);
                $string = substr_replace($string, $regexp, $index + $offset, $length);
                $offset += strlen($regexp) - $length;
            }
        }
    }
}
