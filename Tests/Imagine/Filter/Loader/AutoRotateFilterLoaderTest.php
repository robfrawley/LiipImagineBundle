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

use Liip\ImagineBundle\Imagine\Filter\Loader\AutoRotateFilterLoader;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Imagine\Filter\Loader\AutoRotateFilterLoader
 */
class AutoRotateFilterLoaderTest extends AbstractTest
{
    private $orientationKey = 'exif.Orientation';

    /*
     * Possible rotation values
     * 1: 0°
     * 2: 0° flipped horizontally
     * 3: 180°
     * 4: 180° flipped horizontally
     * 5: 90° flipped horizontally
     * 6: 90°
     * 7: -90° flipped horizontally
     * 8: -90°
     * No metadata means no rotation nor flip.
     */

    /**
     * 1: no rotation.
     */
    public function testLoadExif1()
    {
        $this->loadExif('1', null, false);
    }

    /**
     * 2: no rotation flipped horizontally.
     */
    public function testLoadExif2()
    {
        $this->loadExif('2', null, true);
    }

    /**
     * 3: 180°.
     */
    public function testLoadExif3()
    {
        $this->loadExif('3', 180, false);
    }

    /**
     * 4: 180° flipped horizontally.
     */
    public function testLoadExif4()
    {
        $this->loadExif('4', 180, true);
    }

    /**
     * 5: 90° flipped horizontally.
     */
    public function testLoadExif5()
    {
        $this->loadExif('5', 90, true);
    }

    /**
     * 6: 90°.
     */
    public function testLoadExif6()
    {
        $this->loadExif('6', 90, false);
    }

    /**
     * 7: -90° flipped horizontally.
     */
    public function testLoadExif7()
    {
        $this->loadExif('7', -90, true);
    }

    /**
     * 8: -90°.
     */
    public function testLoadExif8()
    {
        $this->loadExif('8', -90, false);
    }

    /**
     * Theoretically Orientation is `short` (uint16), so it could be anything
     * from [0; 65535].
     */
    public static function getInvalidOrientations()
    {
        return [[0, 9, 255, 65535]];
    }

    /**
     * @dataProvider getInvalidOrientations
     *
     * @param string $orientation
     */
    public function testLoadExifInvalid($orientation)
    {
        $this->loadExif($orientation, null, false);
    }

    /**
     * No rotation info: no rotation nor flip.
     */
    public function testLoadExifNull()
    {
        $this->loadExif(null, null, false);
    }

    /**
     * Starts a test with expected results.
     *
     * @param string   $exifValue             The exif value to be returned by the metadata mock
     * @param null|int $expectCallRotateValue {null|number} The expected rotation value, null if no rotation is expected
     * @param bool     $expectCallFlip        True if a horizontal flip is expected, false otherwise
     */
    private function loadExif($exifValue, $expectCallRotateValue, $expectCallFlip)
    {
        $loader = new AutoRotateFilterLoader();

        // Mocks the image and makes it use the fake meta data.
        $image = $this->getImageInterfaceMock();

        if (method_exists('\Imagine\Image\ImageInterface', 'metadata')) {
            // Mocks the metadata and makes it return the expected exif value for the rotation.
            // If $exifValue is null, it means the image doesn't contain any metadata.
            $metaData = $this->getMetadataBagMock();

            $metaData
                ->expects($this->atLeastOnce())
                ->method('offsetGet')
                ->willReturn($exifValue);

            if ($exifValue && $exifValue > '1' && $exifValue <= 8) {
                $metaData
                    ->expects($this->once())
                    ->method('offsetSet')
                    ->with($this->orientationKey, '1');
            }

            $image
                ->expects($this->atLeastOnce())
                ->method('metadata')
                ->willReturn($metaData);
        } else {
            $jpg = file_get_contents(__DIR__.'/../../../Fixtures/images/pixel_1x1_orientation_at_0x30.jpg');
            // The byte with orientation is at offset 0x30 for this image
            $jpg[0x30] = chr((int) $exifValue);

            $image
                ->expects($this->once())
                ->method('get')
                ->with('jpg')
                ->will($this->returnValue($jpg));
        }

        // Checks that rotate is called with $expectCallRotateValue, or not called at all if $expectCallRotateValue is null.
        $image
            ->expects(null !== $expectCallRotateValue ? $this->once() : $this->never())
            ->method('rotate')
            ->with($expectCallRotateValue);

        // Checks that rotate is called if $expectCallFlip is true, not called if $expectCallFlip is false.
        $image
            ->expects($expectCallFlip ? $this->once() : $this->never())
            ->method('flipHorizontally');

        $loader->load($image);
    }
}
