<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Templating;

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
    public static function provideFilterData()
    {
        return array(
            // DO keep origin query string and DO append output query string (default)
            array('abc.png', 'abc.png', '/cache/abc.png', '/cache/abc.png', false, true),

            // DO keep origin query string and DO append output query string (default)
            array('abc.png?foo=bar', 'abc.png?foo=bar', '/cache/abc.png?foo=bar', '/cache/abc.png?foo=bar', false, true),

            // DO keep origin query string and DO append output query string (default)
            array('abc.png?foo=bar', 'abc.png?foo=bar', '/cache/abc.png?baz=qux', '/cache/abc.png?baz=qux&foo=bar', false, true),

            // DO keep origin query string and do NOT append output query string
            array('abc.png?foo=bar', 'abc.png?foo=bar', '/cache/abc.png?baz=qux', '/cache/abc.png?baz=qux', false, false),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png', '/cache/abc.png?foo=bar', '/cache/abc.png?foo=bar', true, false),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png?baz=qux', '/cache/abc.png?foo=bar', '/cache/abc.png?foo=bar&baz=qux', false, true),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png', '/cache/abc.png', '/cache/abc.png?baz=qux', true, true),

            // DO keep origin query string and do NOT append output query string
            array('abc.png?baz=qux', 'abc.png', '/cache/abc.png', '/cache/abc.png', true, false),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png', '/cache/abc.png', '/cache/abc.png', true, false),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png?baz=qux', '/cache/abc.png?foo=bar', '/cache/abc.png?foo=bar&baz=qux', false, true),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png', '/cache/abc.png?foo=bar', '/cache/abc.png?foo=bar&baz=qux', true, true),

            // DO keep origin query string and DO append output query string
            array('abc.png?baz=qux', 'abc.png', '/cache/abc.png?foo=bar', '/cache/abc.png?foo=bar&baz=qux', true, true),
        );
    }

    /**
     * @param string $filterPath
     * @param string $requestedPath
     * @param string $resolvedPath
     * @param string $finalPath
     * @param bool   $uriQueryOriginRemove
     * @param bool   $uriQueryOutputAppend
     *
     * @dataProvider provideFilterData
     */
    public function testFilter($filterPath, $requestedPath, $resolvedPath, $finalPath, $uriQueryOriginRemove, $uriQueryOutputAppend)
    {
        $cacheManager = $this->createCacheManagerMock();

        $cacheManager
            ->method('getBrowserPath')
            ->with($requestedPath)
            ->will($this->returnValue($resolvedPath));

        $extension = new ImagineExtension($cacheManager);
        $extension->setUriQueryBehavior($uriQueryOriginRemove, $uriQueryOutputAppend);

        $this->assertEquals($finalPath, $extension->filter($filterPath, 'foo'));
    }
}
