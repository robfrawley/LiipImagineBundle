<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Filter;

use Liip\ImagineBundle\Imagine\Filter\Loader\WatermarkFilterLoader;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Imagine\Filter\Loader\WatermarkFilterLoader
 */
class WatermarkFilterLoaderTest extends AbstractTest
{
    public function testLoad()
    {
        return;
        $loader = new WatermarkFilterLoader();

        $mockImageSize = new Box(
            self::DUMMY_IMAGE_WIDTH,
            self::DUMMY_IMAGE_HEIGHT
        );
        $image = $this->getImageInterfaceMock();
        $image->method('getSize')->willReturn($mockImageSize);
        $image->method('copy')->willReturn($image);
        $image->expects($this->once())
            ->method('thumbnail')
            ->with($expected)
            ->willReturn($image);

        $options = [];
        $options['size'] = [$width, $height];
        $options['allow_upscale'] = true;

        $loader->load($image, $options);
    }
}
