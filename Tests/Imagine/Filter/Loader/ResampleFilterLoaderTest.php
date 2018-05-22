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

use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Exception\Imagine\Filter\FilterLoaderException;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Imagine\Filter\Loader\ResampleFilterLoader;
use Liip\ImagineBundle\Tests\AbstractTest;
use Liip\ImagineBundle\Utility\Filesystem\TemporaryFile;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Liip\ImagineBundle\Imagine\Filter\Loader\ResampleFilterLoader
 */
class ResampleFilterLoaderTest extends AbstractTest
{
    /**
     * @var string
     */
    private $driverFQC;

    /**
     * @var ImagineInterface|ImagickImagine|GmagickImagine
     */
    private $imagine;

    /**
     * Setup test environment by detecting a working driver and creating a matching ImagineInterface instance.
     */
    public function setUp()
    {
        parent::setUp();

        $this->imagine = self::getImagineDriverInstance(
            $this->driverFQC = self::getSupportedImageDriverFQC()
        );
    }

    /**
     * @return \Generator|array
     */
    public static function provideResampleData(): \Generator
    {
        $sizes = array(72.0, 120.0, 240.0);
        $paths = [
            realpath(__DIR__.'/../../../Fixtures/assets/cats.png'),
            realpath(__DIR__.'/../../../Fixtures/assets/cats.jpeg'),
        ];

        foreach ($paths as $p) {
            foreach ($sizes as $s) {
                yield [$p, $s];
            }
        }
    }

    /**
     * @dataProvider provideResampleData
     *
     * @param string $imgPath
     * @param float  $resolution
     */
    public function testResample(string $imgPath, float $resolution): void
    {
        ($temporary = new TemporaryFile(
            sprintf('resample-filter-loader-test.%s', pathinfo($imgPath, PATHINFO_EXTENSION))
        ))->acquire();

        $this
            ->createResampleFilterLoaderInstance($this->imagine)
            ->load($this->imagine->open($imgPath), array(
                'x' => $resolution,
                'y' => $resolution,
                'unit' => 'ppc',
            ))
            ->save($temporary->stringifyFile());

        $this->assertSame([
            'x' => $resolution,
            'y' => $resolution,
        ], $this->getImageResolution($temporary->getContents()));
    }

    /**
     * @return \Generator
     */
    public static function provideOptionsData(): \Generator
    {
        yield array(array('x' => 500, 'y' => 500, 'unit' => 'ppi'));
        yield array(array('x' => 500, 'y' => 500, 'unit' => 'ppc'));
        yield array(array('x' => 120, 'y' => 120, 'unit' => 'ppi', 'filter' => 'undefined'));
        yield array(array('x' => 120, 'y' => 120, 'unit' => 'ppi', 'filter' => 'filter_undefined'));
        yield array(array('x' => 120, 'y' => 120, 'unit' => 'ppi', 'filter' => 'lanczos'));
        yield array(array('x' => 120, 'y' => 120, 'unit' => 'ppi', 'filter' => 'filter_lanczos'));
    }

    /**
     * @param array $options
     *
     * @dataProvider provideOptionsData
     */
    public function testOptions(array $options)
    {
        ($image = $this->getImageInterfaceMock())
            ->expects($this->once())
            ->method('save')
            ->willReturn($image);

        ($imagine = $this->createImagineInterfaceMock())
            ->expects($this->once())
            ->method('open')
            ->willReturn($image);

        $this
            ->createResampleFilterLoaderInstance($imagine)
            ->load($image, $options);
    }

    /**
     * @return \Generator
     */
    public static function provideInvalidOptionsData(): \Generator
    {
        yield array(array());

        yield array(array(
            'x' => 'string-is-invalid-type',
            'y' => 120,
            'unit' => 'ppi',
        ));

        yield array(array(
            'x' => 120,
            'y' => array('is', 'invalid', 'type'),
            'unit' => 'ppi',
        ));

        yield array(array(
            'x' => 120,
            'y' => 120,
            'unit' => 'invalid-value',
        ));
    }

    /**
     * @dataProvider provideInvalidOptionsData
     */
    public function testThrowsOnInvalidOptions(array $options)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option(s) passed to Liip\ImagineBundle\Imagine\Filter\Loader\ResampleFilterLoader::load().');

        $this
            ->createResampleFilterLoaderInstance()
            ->load($this->getImageInterfaceMock(), $options);
    }

    public function testThrowsOnInvalidFilterOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for "filter" option: must be a valid constant resolvable using one of formats "\Imagine\Image\ImageInterface::FILTER_%s", "\Imagine\Image\ImageInterface::%s", or "%s".');

        $this
            ->createResampleFilterLoaderInstance()
            ->load($this->getImageInterfaceMock(), array(
                'x' => 120,
                'y' => 120,
                'unit' => 'ppi',
                'filter' => 'invalid-filter',
            )
        );
    }

    public function testThrowsOnInvalidTemporaryPathOption()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('{Unable to create temporary file in ".+" base path.}');

        $this
            ->createResampleFilterLoaderInstance()
            ->load($this->getImageInterfaceMock(), array(
                'x' => 120,
                'y' => 120,
                'unit' => 'ppi',
                'temp_dir' => '/this/path/does/not/exist/foo/bar/baz/qux',
            )
        );
    }

    public function testThrowsOnSaveOrOpenError(): void
    {
        $this->expectException(FilterLoaderException::class);
        $this->expectExceptionMessage('IDK');

        ($image = $this->getImageInterfaceMock())
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Error saving file!'));

        $this
            ->createResampleFilterLoaderInstance()
            ->load($image, array(
                'x' => 120,
                'y' => 120,
                'unit' => 'ppi',
            )
        );
    }

    /**
     * @param ImagineInterface|MockObject $imagine
     *
     * @return ResampleFilterLoader
     */
    private function createResampleFilterLoaderInstance($imagine = null): ResampleFilterLoader
    {
        return new ResampleFilterLoader($imagine ?: $this->createImagineInterfaceMock());
    }

    /**
     * @param string $blob
     *
     * @return float[]
     */
    private function getImageResolution(string $blob): array
    {
        try {
            $driver = \Imagick::class === $this->driverFQC
                ? new \Imagick()
                : new \Gmagick();
        } catch (\ImagickException $e) {
            $this->markTestSkipped(sprintf(
                'Test "%s" failed to instantiate a "\Imagick" or "\Gmagick" instance: %s', __CLASS__, $e->getMessage()
            ));
        }

        try {
            return $driver->readImageBlob($blob)->getImageResolution();
        } catch (\GmagickException $e) {
            $this->markTestSkipped(sprintf(
                'Test "%s" failed to read in image blob using "\Gmagick" instance: %s', __CLASS__, $e->getMessage()
            ));
        }
    }

    /**
     * @param string $driver
     *
     * @return ImagickImagine|GmagickImagine
     */
    private static function getImagineDriverInstance(string $driver)
    {
        return \Imagick::class === $driver
            ? new ImagickImagine()
            : new GmagickImagine();
    }

    /**
     * @return string
     */
    private static function getSupportedImageDriverFQC(): string
    {
        if (class_exists($driver = \Imagick::class)) {
            return $driver;
        }

        if (class_exists($driver = \Gmagick::class)) {
            return $driver;
        }

        static::markTestSkipped(sprintf(
            'Test "%s" requires the "\Imagick" class or the "\Gmagick" class be available through either the '.
            '"imagick" or "gmagick" extensions, respectively. Ensure one of those extensions is properly installed in '.
            'your environment, and that it is configured to load with the PHP SAPI you are using to run these tests.',
            __CLASS__
        ));
    }
}
