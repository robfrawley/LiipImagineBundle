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
    foreach ($fixture->data('status', 'results') as $s) {
        $style->status($s)->newline(2);
    }
};
