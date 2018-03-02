<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Console\Style\Helper;

use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Console\Style\StyleOptions;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class BlockHelper implements StyleOptions
{
    use BlockHelperTrait;

    /**
     * @param string|string[] $strings
     * @param Markup|null     $markup
     * @param string|null     $type
     * @param string|null     $prefix
     * @param int             $options
     *
     * @return self
     */
    public function __invoke($strings, Markup $markup = null, string $type = null, string $prefix = null, int $options = Style::DEFAULT_BLOCK_MD): self
    {
        return $this->block($strings, $markup, $type, $prefix, $options);
    }

    /**
     * @param string|string[] $strings
     * @param Markup|null     $markup
     * @param string|null     $type
     * @param string|null     $prefix
     * @param int             $options
     *
     * @return self
     */
    public function small($strings, Markup $markup = null, string $type = null, string $prefix = null, int $options = Style::DEFAULT_BLOCK_SM): self
    {
        return $this->block($strings, $markup, $type, $prefix, $options);
    }

    /**
     * @param string|string[] $strings
     * @param Markup|null     $markup
     * @param string|null     $type
     * @param string|null     $prefix
     * @param int             $options
     *
     * @return self
     */
    public function large($strings, Markup $markup = null, string $type = null, string $prefix = null, int $options = Style::DEFAULT_BLOCK_LG): self
    {
        return $this->block($strings, $markup, $type, $prefix, $options);
    }

    /**
     * @param string  $string
     * @param mixed[] ...$replacements
     *
     * @return self
     */
    public function okay(string $string, ...$replacements): self
    {
        return $this->small($this->compile($string, $replacements), new Markup('black', 'green'), 'OK', '-');
    }

    /**
     * @param string  $string
     * @param mixed[] ...$replacements
     *
     * @return self
     */
    public function note(string $string, ...$replacements): self
    {
        return $this->small($this->compile($string, $replacements), new Markup('yellow', 'black'), 'NOTE', '/');
    }

    /**
     * @param string  $string
     * @param mixed[] ...$replacements
     *
     * @return self
     */
    public function crit(string $string, ...$replacements): self
    {
        return $this->large($this->compile($string, $replacements), new Markup('white', 'red'), 'ERROR', '#');
    }
}
