<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Context;

/**
 * @internal
 */
class UriContext
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var string|null
     */
    private $query;

    /**
     * @var string|null
     */
    private $fragment;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $this->parseUriBase($uri, $this->query = $this->parseUriQuery($uri), $this->fragment = $this->parseUriFragment($uri));
    }

    /**
     * @param bool $withQueryAndFragment
     *
     * @return string
     */
    public function getUri($withQueryAndFragment = true)
    {
        return $withQueryAndFragment ? $this->buildUri() : $this->uri;
    }

    /**
     * @return string|null
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return bool
     */
    public function hasQuery()
    {
        return $this->query !== null;
    }

    /**
     * @param string|null $query
     *
     * @return $this
     */
    public function addQuery($query = null)
    {
        if ($query && false === strpos($query, $this->query)) {
            $this->query = strlen($this->query) > 0 ? sprintf('%s&%s', $this->query, $query) : $query;
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @return bool
     */
    public function hasFragment()
    {
        return $this->fragment !== null;
    }

    /**
     * @return string
     */
    private function buildUri()
    {
        $uri = $this->uri;

        if ($this->query) {
            $uri = sprintf('%s?%s', $uri, $this->query);
        }

        if ($this->fragment) {
            $uri = sprintf('%s#%s', $uri, $this->fragment);
        }

        return $uri;
    }

    /**
     * @param string      $uri
     * @param string|null $query
     * @param string|null $anchor
     *
     * @return string
     */
    private function parseUriBase($uri, $query, $anchor)
    {
        if (null !== $anchor) {
            $uri = str_replace(sprintf('#%s', $anchor), '', $uri);
        }

        if (null !== $query) {
            $uri = substr($uri, 0, strlen($uri) - strlen($query) - 1);
        }

        return $uri;
    }

    /**
     * @param string $uri
     *
     * @return string|null
     */
    private function parseUriQuery($uri)
    {
        return parse_url($uri, PHP_URL_QUERY) ?: null;
    }

    /**
     * @param string $uri
     *
     * @return null|string
     */
    private function parseUriFragment($uri)
    {
        return parse_url($uri, PHP_URL_FRAGMENT) ?: null;
    }
}
