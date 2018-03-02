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

use Liip\ImagineBundle\Console\Style\StyleOptions;
use Symfony\Component\Console\Terminal;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class LineHelper implements StyleOptions
{
    /**
     * @var int
     */
    private const MAX_LINE_LENGTH = 120;

    /**
     * @return int
     */
    public static function length(): int
    {
        return ((new Terminal())->getWidth() ?? self::MAX_LINE_LENGTH) - (int) (DIRECTORY_SEPARATOR === '\\');
    }
}
