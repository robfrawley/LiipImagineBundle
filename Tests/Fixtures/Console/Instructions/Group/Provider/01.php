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
use Liip\ImagineBundle\Tests\Fixtures\Console\FixtureProvider;

return function (Style $style, FixtureProvider $fixture) {
    foreach ($fixture->data('group', 'items') as $item => $group) {
        $style
            ->group($item, $group)
            ->newline(2)
            ->group($item, $group, new Markup(MarkupColors::COLOR_BLUE))
            ->newline(2)
            ->group($item, $group, new Markup(MarkupColors::COLOR_WHITE, MarkupColors::COLOR_WHITE))
            ->newline(2);
    }
};
