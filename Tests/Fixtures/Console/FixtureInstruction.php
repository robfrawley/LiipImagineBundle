<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Fixtures\Console;

class FixtureInstruction
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $expected;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var int
     */
    private $index;

    /**
     * @var FixtureProvider
     */
    private $fixture;

    /**
     * @param string          $provider
     * @param string          $expected
     * @param FixtureProvider $fixture
     */
    public function __construct(string $provider, string $expected, FixtureProvider $fixture)
    {
        $this->provider = $provider;
        $this->expected = $expected;
        $this->fixture = $fixture;

        list($this->namespace, $this->index) = static::parseNamespaceAndIndexFromFilename($provider);
    }

    /**
     * @return string
     */
    public function providerFilePath(): string
    {
        return $this->provider;
    }

    /**
     * @return \Closure
     */
    public function provider(): \Closure
    {
        $provider = require $this->provider;

        return function (...$arguments) use ($provider) {
            return $provider(...$arguments);
        };
    }

    /**
     * @return string
     */
    public function expectedFilePath(): string
    {
        return $this->expected;
    }

    /**
     * @return string
     */
    public function expected(): string
    {
        if (false === $contents = file_get_contents($this->expected)) {
            throw new \RuntimeException(sprintf('Failed to read instruction expected file "%s".', $this->expected));
        }

        return $contents;
    }

    /**
     * @return string
     */
    public function ns(): string
    {
        return $this->namespace;
    }

    /**
     * @return int
     */
    public function index(): int
    {
        return $this->index;
    }

    /**
     * @return FixtureProvider
     */
    public function fixture(): FixtureProvider
    {
        return $this->fixture;
    }

    /**
     * @param string $file
     *
     * @return string[]|int[]
     */
    private static function parseNamespaceAndIndexFromFilename(string $file): array
    {
        if (1 !== preg_match('{/(?<context>[^/]+)/(?:Expected|Provider)/[0-9]+.(?:php|txt)$}', $file, $match)) {
            throw new \RuntimeException(sprintf('Failed to parse context from instruction path "%s".', $file));
        }

        return [
            FixtureProvider::transformCamelCaseToKebabCase($match['context']),
            (int) pathinfo($file, PATHINFO_FILENAME),
        ];
    }
}
