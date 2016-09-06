<?php

namespace Liip\ImagineBundle\Tests\Binary\Loader;

use Liip\ImagineBundle\Binary\Loader\StreamLoader;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers Liip\ImagineBundle\Binary\Loader\StreamLoader<extended>
 */
class StreamLoaderTest extends AbstractTest
{
    public function testThrowsIfInvalidPathGivenOnFind()
    {
        $loader = static::createLoaderInstance('file://');

        $path = $this->tempDir.'/invalid.jpeg';

        $this->setExpectedException(
            'Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException',
            'Source image file://'.$path.' not found.'
        );

        $loader->find($path);
    }

    public function testReturnImageContentOnFind()
    {
        $expectedContent = file_get_contents($this->fixturesDir.'/assets/cats.jpeg');

        $loader = static::createLoaderInstance('file://');

        $this->assertSame(
            $expectedContent,
            $loader->find($this->fixturesDir.'/assets/cats.jpeg')
        );
    }

    public function testReturnImageContentWhenStreamContextProvidedOnFind()
    {
        $expectedContent = file_get_contents($this->fixturesDir.'/assets/cats.jpeg');

        $context = stream_context_create();

        $loader = static::createLoaderInstance('file://', $context);

        $this->assertSame(
            $expectedContent,
            $loader->find($this->fixturesDir.'/assets/cats.jpeg')
        );
    }

    public function testThrowsIfInvalidResourceGivenInConstructor()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given context is no valid resource.');

        static::createLoaderInstance('no valid resource', true);
    }

    /**
     * @param string     $prefix
     * @param null|mixed $context
     *
     * @return StreamLoader
     */
    public static function createLoaderInstance($prefix, $context = null)
    {
        return new StreamLoader($prefix, $context);
    }
}
