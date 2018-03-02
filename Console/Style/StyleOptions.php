<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Console\Style;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
interface StyleOptions
{
    /**
     * @var int
     */
    public const MARKUP_ESCAPE = 1;

    /**
     * @var int
     */
    public const MARKUP_STRIP = 2;

    /**
     * @var int
     */
    public const PAD_WHOLE_X = 4;

    /**
     * @var int
     */
    public const PAD_WHOLE_Y = 8;

    /**
     * @var int
     */
    public const PAD_WHOLE = self::PAD_WHOLE_X | self::PAD_WHOLE_Y;

    /**
     * @var int
     */
    public const PAD_PARAGRAPHS = 16;

    /**
     * @var int
     */
    public const PAD_HEADER_X = 32;

    /**
     * @var int
     */
    public const PAD_HEADER_Y = 64;

    /**
     * @var int
     */
    public const PAD_HEADER = self::PAD_HEADER_X | self::PAD_HEADER_Y;

    /**
     * @var int
     */
    public const POS_HEADER_BLOCK = 128;

    /**
     * @var int
     */
    public const POS_HEADER_INLINE = 256;

    /**
     * @var int
     */
    public const POS_HEADER_COLUMN = 512;

    /**
     * @var int
     */
    public const STYLE_EM_HEADER = 1024;

    /**
     * @var int
     */
    public const STYLE_EM_PREFIX = 2048;

    /**
     * @var int
     */
    public const STYLE_EM_BODY = 4096;

    /**
     * @var int
     */
    public const STYLE_EM = self::STYLE_EM_HEADER | self::STYLE_EM_PREFIX | self::STYLE_EM_BODY;

    /**
     * @var int
     */
    public const STYLE_REVERSE_HEADER = 8192;

    /**
     * @var int
     */
    public const STYLE_REVERSE_PREFIX = 16384;

    /**
     * @var int
     */
    public const STYLE_REVERSE_BODY = 32768;

    /**
     * @var int
     */
    public const STYLE_REVERSE = self::STYLE_REVERSE_HEADER | self::STYLE_REVERSE_PREFIX | self::STYLE_REVERSE_BODY;

    /**
     * @var int
     */
    public const STYLE_INDENT_PARAGRAPHS = 65536;

    /**
     * @var int
     */
    public const DEFAULT_BLOCK_SM = self::PAD_WHOLE_X | self::POS_HEADER_INLINE | self::PAD_HEADER_X | self::STYLE_INDENT_PARAGRAPHS;

    /**
     * @var int
     */
    public const DEFAULT_BLOCK_MD = self::PAD_WHOLE | self::PAD_PARAGRAPHS | self::STYLE_EM_HEADER | self::POS_HEADER_INLINE | self::PAD_HEADER_X;

    /**
     * @var int
     */
    public const DEFAULT_BLOCK_LG = self::PAD_WHOLE | self::PAD_PARAGRAPHS | self::STYLE_EM_HEADER | self::POS_HEADER_BLOCK | self::PAD_HEADER;

    /**
     * @var int
     */
    public const DEFAULT_TITLE = self::PAD_WHOLE | self::POS_HEADER_INLINE | self::STYLE_REVERSE;
}
