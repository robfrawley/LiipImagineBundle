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

use Liip\ImagineBundle\Templating\Helper\ImagineHelper;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Templating\Helper\ImagineHelper
 */
class ImagineHelperTest extends AbstractTest
{
    public function testInstanceOfSymfonyTemplatingHelper()
    {
        $rc = new \ReflectionClass('Liip\ImagineBundle\Templating\Helper\ImagineHelper');
        $this->assertTrue($rc->isSubclassOf('Symfony\Component\Templating\Helper\Helper'));
    }

    public function testConstruction()
    {
        new ImagineHelper($this->getMockCacheManager());
    }

    public function testNameGetter()
    {
        $helper = new ImagineHelper($this->getMockCacheManager());
        $this->assertEquals('liip_imagine', $helper->getName());
    }

    public function testProxyCallToCacheManagerOnFilter()
    {
        $cache = $this->getMockCacheManager();
        $cache
            ->expects($this->once())
            ->method('getBrowserPath')
            ->with($expectedPath = 'thePathToTheImage', $expectedFilter = 'thumbnail')
            ->will($this->returnValue($expectedCachePath = 'thePathToTheCachedImage'));

        $helper = new ImagineHelper($cache);

        $this->assertEquals($expectedCachePath, $helper->filter($expectedPath, $expectedFilter));
    }
}
