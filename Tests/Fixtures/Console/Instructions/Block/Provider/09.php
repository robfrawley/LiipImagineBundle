<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Tests\Fixtures\Console\FixtureProvider;

return function (Style $style, FixtureProvider $fixture) {
    $style->blocks()($fixture->data('block', 'lines'), null, 'PAD HEADER+WHOLE EMPHASIS REVERSE', 'PREFIX', Style::PAD_HEADER | Style::PAD_WHOLE | Style::PAD_PARAGRAPHS | Style::STYLE_EM | Style::STYLE_REVERSE);
};
