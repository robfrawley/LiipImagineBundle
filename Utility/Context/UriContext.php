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
    private $base;

    /**
     * @var string[]
     */
    private $query = [];

    /**
     * @var string|null
     */
    private $fragment;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->query = $this->parseQuery($uri);
        $this->fragment = $this->parseFragment($uri);
        $this->base = $this->parseBase($uri);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUri();
    }

    /**
     * @param bool $withQueryAndFragment
     *
     * @return string
     */
    public function getUri($withQueryAndFragment = true)
    {
        return $withQueryAndFragment ? $this->buildUri() : $this->base;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return http_build_query($this->query);
    }

    /**
     * @return bool
     */
    public function hasQuery()
    {
        return 0 !== count($this->query);
    }

    /**
     * @param string|null $query
     *
     * @return $this
     */
    public function addQuery($query = null)
    {
        parse_str($query, $queryParsed);

        foreach ((array) $queryParsed as $k => $v) {
            $this->query[$k] = $v;
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
     * @param string $fragment
     *
     * @return $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * @return string
     */
    private function buildUri()
    {
        $uri = $this->base;

        if ($this->hasQuery()) {
            $uri .= sprintf('?%s', $this->getQuery());
        }

        if ($this->hasFragment()) {
            $uri .= sprintf('#%s', $this->getFragment());
        }

        return $uri;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    private function parseBase($uri)
    {
        if ($this->hasFragment()) {
            $uri = preg_replace(sprintf('{#%s$}', preg_quote($this->getFragment())), '', $uri);
        }

        if ($this->hasQuery()) {
            $uri = preg_replace(sprintf('{\?%s$}', preg_quote($this->getQuery())), '', $uri);
        }

        return $uri;
    }

    /**
     * @param string $uri
     *
     * @return string[]
     */
    private function parseQuery($uri)
    {
        parse_str(parse_url($uri, PHP_URL_QUERY) ?: '', $query);

        return $query;
    }

    /**
     * @param string $uri
     *
     * @return null|string
     */
    private function parseFragment($uri)
    {
        return parse_url($uri, PHP_URL_FRAGMENT) ?: null;
    }
}
