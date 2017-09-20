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

class InvalidOptionException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @param string $message
     * @param array  $options
     */
    public function __construct($message, array $options = array())
    {
        parent::__construct(sprintf('Invalid post-processor configuration provided (%s) with options %s.',
            $message, $this->stringifyOptions($options)));
    }

    /**
     * @param array $options
     *
     * @return string
     */
    private function stringifyOptions(array $options = array())
    {
        if (0 === count($options)) {
            return '[]';
        }

        $options = array_map(array($this, 'stringifyOptionValue'), $options);

        array_walk($options, function (&$o, $name) {
            $o = sprintf('%s="%s"', $name, $o);
        });

        return sprintf('[%s]', implode(', ', $options));
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function stringifyOptionValue($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_scalar($value)) {
            return $value;
        }

        return str_replace("\n", '', var_export($value, true));
    }
}
