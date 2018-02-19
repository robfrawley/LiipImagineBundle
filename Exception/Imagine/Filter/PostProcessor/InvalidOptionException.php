<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Exception\Imagine\Filter\PostProcessor;

use Liip\ImagineBundle\Exception\ExceptionInterface;

final class InvalidOptionException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @param string $message
     * @param array  $options
     */
    public function __construct($message, array $options = [])
    {
        parent::__construct(vsprintf('Invalid post-processor configuration (%s) with options %s.', [
            $message,
            self::stringifyOptions($options),
        ]));
    }

    /**
     * @param array $options
     *
     * @return string
     */
    private static function stringifyOptions(array $options): string
    {
        $options = array_map(function ($mixed): string {
            return self::stringifyOptionValue($mixed);
        }, $options);

        array_walk($options, function (string &$o, string $name): void {
            $o = sprintf('%s="%s"', $name, $o);
        });

        return sprintf('[%s]', implode(', ', $options));
    }

    /**
     * @param mixed $mixed
     *
     * @return string
     */
    private static function stringifyOptionValue($mixed): string
    {
        if (is_array($mixed)) {
            return json_encode($mixed);
        }

        if (is_scalar($mixed)) {
            return $mixed;
        }

        return str_replace("\n", '', var_export($mixed, true));
    }
}
