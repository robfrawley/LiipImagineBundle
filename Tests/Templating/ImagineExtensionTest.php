<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Templating\Helper;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Templating\Helper\ImagineHelper;
use Liip\ImagineBundle\Templating\ImagineExtension;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Templating\ImagineExtension
 */
class ImagineExtensionTest extends AbstractTest
{
    public function testInstanceOfTwigExtension()
    {
        $rc = new \ReflectionClass('Liip\ImagineBundle\Templating\ImagineExtension');
        $this->assertTrue($rc->isSubclassOf('Twig_Extension'));
    }

    public function testConstruction()
    {
        new ImagineExtension($this->createImagineTemplatingHelper());
    }

    public function testNameGetter()
    {
        $extension = new ImagineExtension($this->createImagineTemplatingHelper());
        $this->assertEquals('liip_imagine', $extension->getName());
    }

    public function testHasFilter()
    {
        $extension = new ImagineExtension($this->createImagineTemplatingHelper());

        $this->assertInternalType('array', $extension->getFilters());
        $this->assertCount(1, $extension->getFilters());
    }

    public function testHasFunctions()
    {
        $extension = new ImagineExtension($this->createImagineTemplatingHelper());

        $this->assertInternalType('array', $extension->getFunctions());
        $this->assertCount(0, $extension->getFunctions());
    }

    public function testInvokeFilter()
    {
        $cache = $this->getMockCacheManager();
        $cache
            ->expects($this->once())
            ->method('getBrowserPath')
            ->with($expectedPath = 'thePathToTheImage', $expectedFilter = 'thumbnail')
            ->will($this->returnValue($expectedCachePath = 'thePathToTheCachedImage'));

        $extension = new ImagineExtension(new ImagineHelper($cache));
        $filters = $extension->getFilters();
        $callable = array_shift($filters)->getCallable();

        $this->assertEquals($expectedCachePath, $callable($expectedPath, $expectedFilter));
    }

    /**
     * @param CacheManager $cacheManager
     *
     * @return ImagineHelper
     */
    private function createImagineTemplatingHelper(CacheManager $cacheManager = null)
    {
        return new ImagineHelper($cacheManager ?: $this->getMockCacheManager());
    }
}
