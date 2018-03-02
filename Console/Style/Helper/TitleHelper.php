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
use Liip\ImagineBundle\Console\Output\MarkupColors;
use Liip\ImagineBundle\Console\Output\MarkupOptions;
use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Console\Style\StyleOptions;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class TitleHelper implements StyleOptions
{
    use BlockHelperTrait;

    /**
     * @param string      $title
     * @param string|null $context
     * @param Markup|null $markup
     * @param string|null $prefix
     * @param int         $options
     *
     * @return self
     */
    public function __invoke(string $title, string $context = null, Markup $markup = null, string $prefix = null, int $options = Style::DEFAULT_TITLE)
    {
        return $this->title($title, $context, $markup, $prefix, $options);
    }

    /**
     * @param string      $title
     * @param string|null $context
     * @param Markup|null $markup
     * @param string      $prefix
     * @param int         $options
     *
     * @return self
     */
    public function title(string $title, string $context = null, Markup $markup = null, string $prefix = null, int $options = Style::DEFAULT_TITLE): self
    {
        if (!$this->style->isStyled()) {
            return $this->unStyledTitle($title, $context, $prefix, $options);
        }

        return $this->block($title, StringHelper::normalizeMarkup($markup, MarkupColors::COLOR_WHITE, MarkupColors::COLOR_BLACK), $context, $prefix ?? '', $options);
    }

    /**
     * @param string      $title
     * @param string|null $name
     * @param string|null $prefix
     * @param int         $options
     *
     * @return self
     */
    private function unStyledTitle(string $title, string $name = null, string $prefix = null, int $options = 0): self
    {
        $char = static::formatChar($prefix ?? '#', $options);
        $name = static::formatType($name, $options);

        $this->autoPrependText();
        $this->style->line($char.$name.$title)->newline();

        return $this;
    }
}
