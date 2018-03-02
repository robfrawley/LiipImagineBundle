<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Console\Output;

interface MarkupColors
{
    /**
     * @var string
     */
    public const COLOR_DEFAULT = 'default';

    /**
     * @var string
     */
    public const COLOR_BLACK = 'black';

    /**
     * @var string
     */
    public const COLOR_RED = 'red';

    /**
     * @var string
     */
    public const COLOR_GREEN = 'green';

    /**
     * @var string
     */
    public const COLOR_YELLOW = 'yellow';

    /**
     * @var string
     */
    public const COLOR_BLUE = 'blue';

    /**
     * @var string
     */
    public const COLOR_MAGENTA = 'magenta';

    /**
     * @var string
     */
    public const COLOR_CYAN = 'cyan';

    /**
     * @var string
     */
    public const COLOR_WHITE = 'white';
}