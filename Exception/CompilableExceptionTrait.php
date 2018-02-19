<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Exception;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
trait CompilableExceptionTrait
{
    /**
     * This method maps a "message composition"-oriented signature to the native constructor; it accepts the same args
     * as {@see sprintf()}, a function otherwise commonly called externally to compile the message string ultimately
     * passed to the exception.
     *
     * You may optionally pass a {@see \Throwable} instance in any position of the variadic portion of this method to
     * set the exception's "previous exception" context; the throwable will not interfere with the string replacements,
     * regardless of its position amoung them.
     *
     * You cannot pass the exception code using this method, but you can assign one per-exception class definition by
     * creating a private constant named USE_EXCEPTION_CODE.
     *
     * Example construction usage:
     * <code>
     *   $ver = 10;
     *   $obj = 'user';
     *   $env = 'dev';
     *
     *   try {
     *     // ...
     *   } catch (\Exception $e) {
     *     throw new CompilableExceptionTrait('Cannot create "%s" in "%s" env with package v%d.', $obj, $env, $ver, $e);
     *   }
     * </code>
     *
     * Results in the following:
     *   - The passed \Exception instance is set as the new exception's "previous exception"
     *   - The exception's message is compiled to: "Cannot create "user" in "dev" env with package v10."
     *
     * Replacement filtering notes:
     *   - ALL instances of \Throwable are filtered out of the replacements array passed to sprintf;
     *   - Only the FIRST passed \Throwable is used for setting the previous exception;
     *   - Any additional \Throwable instances passed after the first one will be silently IGNORED entirely.
     *
     * @param string $format
     * @param mixed  ...$arguments
     *
     * @return string[]|int[]|\Throwable[]
     */
    private function mapToNativeConstructArguments(string $format, ...$arguments): array
    {
        return [
            self::resolveMessage($format, $arguments),
            self::resolveCode(),
            self::resolveThrowable($arguments),
        ];
    }

    /**
     * @param string  $format
     * @param mixed[] $replacements
     *
     * @return string
     */
    private static function resolveMessage(string $format, array $replacements): string
    {
        return @vsprintf($format, self::resolveReplacements($replacements)) ?? $format;
    }

    /**
     * @param mixed[] $arguments
     *
     * @return string[]|int[]|float[]|bool[]
     */
    private static function resolveReplacements(array $arguments): array
    {
        return array_map(function ($r) {
            return self::exportReplacement($r);
        }, array_filter($arguments, function ($argument): bool {
            return !($argument instanceof \Throwable);
        }));
    }

    /**
     * @param string|int|float|bool|object $value
     *
     * @return string|int|float|bool
     */
    private static function exportReplacement($value)
    {
        if (is_callable([$value, '__toString'])) {
            return $value->__toString();
        }

        if (is_scalar($value)) {
            return $value;
        }

        return static::dumpReplacement($value);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private static function dumpReplacement($value): string
    {
        $buffer = '';
        $output = function (string $line) use (&$buffer): void {
            $buffer .= ' '.$line;
        };

        (new CliDumper($output, null,
            AbstractDumper::DUMP_STRING_LENGTH | AbstractDumper::DUMP_LIGHT_ARRAY | AbstractDumper::DUMP_COMMA_SEPARATOR
        ))->dump((new VarCloner())->cloneVar($value));

        return trim($buffer);
    }

    /**
     * @param mixed[] $arguments
     *
     * @return \Throwable|null
     */
    private static function resolveThrowable(array $arguments): ?\Throwable
    {
        return array_values(array_filter($arguments, function ($a): bool {
            return $a instanceof \Throwable;
        }))[0] ?? null;
    }

    /**
     * @return int
     */
    private static function resolveCode(): int
    {
        return @constant('self::USE_EXCEPTION_CODE') ?? 0;
    }
}
