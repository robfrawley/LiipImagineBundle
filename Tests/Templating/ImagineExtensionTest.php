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

use Liip\ImagineBundle\Templating\ImagineExtension;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Templating\ImagineExtension
 */
class ImagineExtensionTest extends AbstractTest
{
    public function testSubClassOfHelper()
    {
        $rc = new \ReflectionClass('\Liip\ImagineBundle\Templating\ImagineExtension');

        $this->assertTrue($rc->isSubclassOf('\Twig_Extension'));
    }

    public function testCouldBeConstructedWithCacheManagerAsArgument()
    {
        new ImagineExtension($this->createCacheManagerMock());
    }

    public function testAllowGetName()
    {
        $extension = new ImagineExtension($this->createCacheManagerMock());

        $this->assertEquals('liip_imagine', $extension->getName());
    }

    public function testProxyCallToCacheManagerOnFilter()
    {
        $expectedPath = 'thePathToTheImage';
        $expectedFilter = 'thumbnail';
        $expectedCachePath = 'thePathToTheCachedImage';

        $cacheManager = $this->createCacheManagerMock();
        $cacheManager
            ->expects($this->once())
            ->method('getBrowserPath')
            ->with($expectedPath, $expectedFilter)
            ->will($this->returnValue($expectedCachePath));

        $extension = new ImagineExtension($cacheManager);

        $this->assertEquals($expectedCachePath, $extension->filter($expectedPath, $expectedFilter));
    }

    public function testAddsFilterMethodToFiltersList()
    {
        $extension = new ImagineExtension($this->createCacheManagerMock());

        $filters = $extension->getFilters();

        $this->assertInternalType('array', $filters);
        $this->assertCount(1, $filters);
    }

    /**
     * @return array
     */
    public function provideFilterData()
    {
        return array(
            // test keeps query string in path and final url
            array('/resolved/abc.png?v=123', 'abc.png?v=123', false, '/resolved/abc.png?v=123'),
            // test strips query string from path and append to final url
            array('/resolved/abc.png', 'abc.png?v=123', true, '/resolved/abc.png?v=123'),
            // test appends query string to existing query string in final url
            array('/resolved/abc.png?foo=bar', 'abc.png?v=123', true, '/resolved/abc.png?foo=bar&v=123'),
        );
    }

    /**
     * @param string $browserPath
     * @param string $filterPath
     * @param string $removeUriQuery
     * @param string $expectedPath
     *
     * @dataProvider provideFilterData
     */
    public function testFilter($browserPath, $filterPath, $removeUriQuery, $expectedPath)
    {
        $cacheManager = $this->createCacheManagerMock();

        $cacheManager
            ->method('getBrowserPath')
            ->will($this->returnValue($browserPath));

        $extension = new ImagineExtension($cacheManager);
        $extension->setRemoveUriQuery($removeUriQuery);

        $this->assertEquals($expectedPath, $extension->filter($filterPath, 'foo'));
    }
}
