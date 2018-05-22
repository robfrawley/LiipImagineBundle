<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Imagine\Filter\Loader;

use Imagine\Filter\Basic\Resize;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

/**
 * Loader for Imagine's basic resize filter.
 *
 * @author Jeremy Mikola <jmikola@gmail.com>
 * @author Rob Frawley 2nd <rmf@src.run>
 */
class ResizeFilterLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ImageInterface $image, array $options = [])
    {
        return (new Resize(new Box(...$this->extractSizingOptions($options['size']))))->apply($image);
    }

    /**
     * @param int|int[] $sizes
     *
     * @return int[]
     */
    private function extractSizingOptions($sizes): array
    {
        if (is_int($sizes)) {
            return [$sizes, $sizes];
        }

        if (array_values($sizes) === $sizes) {
            $sizes['width'] = $sizes[0] ?? null;
            $sizes['height'] = $sizes[1] ?? null;
        }

        return [
            $sizes['width'] ?? null,
            $sizes['height'] ?? null,
        ];
    }
}
