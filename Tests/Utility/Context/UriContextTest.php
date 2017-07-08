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
            array('file.ext', 'file.ext', null, false, null, false),
            array('path/to/file.ext', 'path/to/file.ext', null, false, null, false),
            array('schema://domain.com/path/to/file.ext', 'schema://domain.com/path/to/file.ext', null, false, null, false),
            array('file.ext?foo=bar', 'file.ext', 'foo=bar', true, null, false),
            array('file.ext?foo=bar&bar=baz', 'file.ext', 'foo=bar&bar=baz', true, null, false),
            array('path/to/file.ext?foo=bar', 'path/to/file.ext', 'foo=bar', true, null, false),
            array('path/to/file.ext?foo=bar&bar=baz', 'path/to/file.ext', 'foo=bar&bar=baz', true, null, false),
            array('schema://domain.com/path/to/file.ext?foo=bar', 'schema://domain.com/path/to/file.ext', 'foo=bar', true, null, false),
            array('schema://domain.com/path/to/file.ext?foo=bar&bar=baz', 'schema://domain.com/path/to/file.ext', 'foo=bar&bar=baz', true, null, false),
            array('file.ext#anchor', 'file.ext', null, false, 'anchor', true),
            array('file.ext?foo=bar#anchor', 'file.ext', 'foo=bar', true, 'anchor', true),
        );
    }

    /**
     * @param string      $input
     * @param string|null $noQueryInput
     * @param string|null $query
     * @param bool        $hasQuery
     * @param string|null $anchor
     * @param bool        $hasAnchor
     *
     * @dataProvider provideUriData
     */
    public function testGetters($input, $noQueryInput, $query, $hasQuery, $anchor, $hasAnchor)
    {
        $uri = new UriContext($input);

        $this->assertSame($input, $uri->getUri(true));
        $this->assertSame($noQueryInput, $uri->getUri(false));
        $this->assertSame($query, $uri->getQuery());
        $this->assertSame($hasQuery, $uri->hasQuery());
        $this->assertSame($anchor, $uri->getFragment());
        $this->assertSame($hasAnchor, $uri->hasFragment());
    }

    /**
     * @return array
     */
    public static function provideQueryData()
    {
        return array(
            array('file.ext', null, 'file.ext'),
            array('file.ext', '', 'file.ext'),
            array('file.ext', 'foo=bar', 'file.ext?foo=bar'),
            array('file.ext?foo=bar', 'baz=qux', 'file.ext?foo=bar&baz=qux'),
            array('file.ext?foo=bar&baz=qux', 'quux=quuz&corge=grault', 'file.ext?foo=bar&baz=qux&quux=quuz&corge=grault'),
        );
    }

    /**
     * @param string      $path
     * @param string|null $query
     * @param string      $expected
     *
     * @dataProvider provideQueryData
     */
    public function testAppend($path, $query, $expected)
    {
        $uri = new UriContext($path);
        $uri->addQuery($query);

        $this->assertSame($expected, $uri->getUri());
    }
}
