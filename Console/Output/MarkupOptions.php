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

interface MarkupOptions
{
    /**
     * @var string
     */
    public const OPTION_BOLD = 'bold';

    /**
     * @var string
     */
    public const OPTION_UNDERSCORE = 'underscore';

    /**
     * @var string
     */
    public const OPTION_BLINK = 'blink';

    /**
     * @var string
     */
    public const OPTION_REVERSE = 'reverse';
}