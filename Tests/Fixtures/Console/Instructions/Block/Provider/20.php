<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Output\MarkupColors;
use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Console\Style\StyleOptions;
use Liip\ImagineBundle\Tests\Fixtures\Console\FixtureProvider;

return function (Style $style, FixtureProvider $fixture) {
    $markup = new Markup(MarkupColors::COLOR_WHITE, MarkupColors::COLOR_BLACK);
    $style->blocks()($markup($fixture->data('block', 'lines')), null, $markup('STRIPPED'), $markup('(*)'), StyleOptions::MARKUP_STRIP | Style::PAD_WHOLE_X);
    $style->blocks()($markup($fixture->data('block', 'single', 'string')), null, $markup('STRIPPED'), $markup('(*)'), StyleOptions::MARKUP_STRIP | Style::PAD_WHOLE_X);
    $style->blocks()($markup($fixture->data('block', 'lines')), null, $markup('ESCAPED'), $markup('(*)'), StyleOptions::MARKUP_ESCAPE | Style::PAD_WHOLE_X);
    $style->blocks()($markup($fixture->data('block', 'single', 'string')), null, $markup('ESCAPED'), $markup('(*)'), StyleOptions::MARKUP_ESCAPE | Style::PAD_WHOLE_X);
};
