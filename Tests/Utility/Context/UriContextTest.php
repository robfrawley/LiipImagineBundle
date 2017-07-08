<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Utility\Uri;

use Liip\ImagineBundle\Utility\Context\UriContext;

/**
 * @covers \Liip\ImagineBundle\Utility\Context\UriContext
 */
class UriContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public static function provideUriData()
    {
        return array(
            array('file.ext', 'file.ext', '', null),
            array('path/to/file.ext', 'path/to/file.ext', '', null),
            array('schema://domain.com/path/to/file.ext', 'schema://domain.com/path/to/file.ext', '', null),
            array('file.ext?foo=bar', 'file.ext', 'foo=bar', null),
            array('file.ext?foo=bar&bar=baz', 'file.ext', 'foo=bar&bar=baz', null),
            array('path/to/file.ext?foo=bar', 'path/to/file.ext', 'foo=bar', null),
            array('path/to/file.ext?foo=bar&bar=baz', 'path/to/file.ext', 'foo=bar&bar=baz', null),
            array('schema://domain.com/path/to/file.ext?foo=bar', 'schema://domain.com/path/to/file.ext', 'foo=bar', null),
            array('schema://domain.com/path/to/file.ext?foo=bar&bar=baz', 'schema://domain.com/path/to/file.ext', 'foo=bar&bar=baz', null),
            array('file.ext#anchor', 'file.ext', '', 'anchor'),
            array('file.ext?foo=bar#anchor', 'file.ext', 'foo=bar', 'anchor'),
        );
    }

    /**
     * @param string      $full
     * @param string|null $base
     * @param string|null $query
     * @param string|null $fragment
     *
     * @dataProvider provideUriData
     */
    public function testGetters($full, $base, $query, $fragment)
    {
        $uri = new UriContext($full);

        $this->assertSame($full, $uri->__toString());
        $this->assertSame($full, $uri->getUri(true));
        $this->assertSame($base, $uri->getUri(false));
        $this->assertSame($query, $uri->getQuery());
        $this->assertSame($fragment, $uri->getFragment());

        if ($query) {
            $this->assertTrue($uri->hasQuery());
        } else {
            $this->assertFalse($uri->hasQuery());
        }

        if ($fragment) {
            $this->assertTrue($uri->hasFragment());
        } else {
            $this->assertFalse($uri->hasFragment());
        }
    }

    /**
     * @return array
     */
    public static function provideQueryData()
    {
        return array(
            array('file.ext', '', '', 'file.ext'),
            array('file.ext', '', '', 'file.ext'),
            array('file.ext', '', 'foo=bar', 'file.ext?foo=bar'),
            array('file.ext?foo=bar', 'foo=bar', 'baz=qux', 'file.ext?foo=bar&baz=qux'),
            array('file.ext?foo=bar&baz=qux', 'foo=bar&baz=qux', 'quux=quuz&corge=grault', 'file.ext?foo=bar&baz=qux&quux=quuz&corge=grault'),
            array('file.ext?foo=bar', 'foo=bar', 'foo=bar', 'file.ext?foo=bar'),
            array('file.ext?foo=bar', 'foo=bar', 'foo=baz', 'file.ext?foo=baz'),
            array('file.ext?foobar=baz', 'foobar=baz', 'bar=baz', 'file.ext?foobar=baz&bar=baz'),
        );
    }

    /**
     * @param string $path
     * @param string $query
     * @param string $addQuery
     * @param string $expected
     *
     * @dataProvider provideQueryData
     */
    public function testQuery($path, $query, $addQuery, $expected)
    {
        $uri = new UriContext($path);

        if ($query) {
            $this->assertTrue($uri->hasQuery());
        } else {
            $this->assertFalse($uri->hasQuery());
        }

        $this->assertSame($query, $uri->getQuery());
        $this->assertSame($expected, $uri->addQuery($addQuery)->getUri());
    }

    /**
     * @return array
     */
    public static function provideFragmentData()
    {
        return array(
            array('file.ext', null, null, 'file.ext'),
            array('file.ext', null, 'bar', 'file.ext#bar'),
            array('file.ext#foo', 'foo', null, 'file.ext#foo'),
            array('file.ext#foo', 'foo', 'bar', 'file.ext#bar'),
            array('file.ext?foo=bar', null, null, 'file.ext?foo=bar'),
            array('file.ext?foo=bar', null, 'bar', 'file.ext?foo=bar#bar'),
            array('file.ext?foo=bar#foo', 'foo', null, 'file.ext?foo=bar#foo'),
            array('file.ext?foo=bar#foo', 'foo', 'bar', 'file.ext?foo=bar#bar'),
        );
    }

    /**
     * @param string      $path
     * @param string|null $fragment
     * @param string|null $setFragment
     * @param string      $expected
     *
     * @dataProvider provideFragmentData
     */
    public function testFragment($path, $fragment, $setFragment, $expected)
    {
        $uri = new UriContext($path);

        if ($fragment) {
            $this->assertTrue($uri->hasFragment());
        } else {
            $this->assertFalse($uri->hasFragment());
        }

        $this->assertSame($fragment, $uri->getFragment());

        if ($setFragment) {
            $uri->setFragment($setFragment);
        }

        $this->assertSame($expected, $uri->getUri());
    }
}
