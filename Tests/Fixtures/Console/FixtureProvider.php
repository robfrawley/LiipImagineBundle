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

class FixtureProvider
{
    /**
     * @var string
     */
    private $root;

    /**
     * @var array[]
     */
    private $data;

    /**
     * @var FixtureInstruction[]
     */
    private $instructions;

    /**
     * @param string $root
     */
    public function __construct(string $root = __DIR__)
    {
        $this->root = $root;
    }

    /**
     * @param string|null $namespace
     * @param string[]    ...$indexes
     *
     * @return string|string[]|array|array[]
     */
    public function data(string $namespace = null, string ...$indexes)
    {
        $this->loadStaticData();

        if (!isset($this->data[$namespace])) {
            throw new \InvalidArgumentException(sprintf('Failed to fetch data for "%s" namespace.', $namespace));
        }

        $data = $this->data[$namespace];

        foreach ($indexes as $index) {
            if (!isset($data[$index])) {
                throw new \InvalidArgumentException(sprintf('Failed to fetch data index "%s" (at %s) for "%s" namespace.', implode('.', $indexes), $index, $namespace));
            }

            $data = $data[$index];
        }

        return $data;
    }

    /**
     * @return array[]
     */
    public function allData(): array
    {
        $this->loadStaticData();

        return $this->data;
    }

    /**
     * @param string $namespace
     * @param int    $index
     *
     * @return FixtureInstruction
     */
    public function instruction(string $namespace, int $index = 1): FixtureInstruction
    {
        $this->loadInstructions();

        foreach ($this->instructions as $instruction) {
            if ($instruction->ns() === $namespace && $instruction->index() === $index) {
                return $instruction;
            }
        }

        throw new \InvalidArgumentException(sprintf('Failed to fetch instruction index "%02d" for "%s" namespace.', $index, $namespace));
    }

    /**
     * @return FixtureInstruction[]
     */
    public function allInstructions(): array
    {
        $this->loadInstructions();

        return $this->instructions;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function transformCamelCaseToKebabCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
    }

    /**
     * Load static data if no fixture data exists yet.
     */
    public function loadStaticData(): void
    {
        if (!empty($this->data)) {
            return;
        }

        foreach (glob($this->root.'/Instructions/*/data.json') as $file) {
            $this->data[static::parseStaticFileContext($file)] = static::readStaticJson($file);
        }
    }

    /**
     * Load instruction data if no fixture data exists yet.
     */
    private function loadInstructions(): void
    {
        if (!empty($this->instructions)) {
            return;
        }

        foreach (array_map(null, glob($this->root.'/Instructions/*/Provider/*.php'), glob($this->root.'/Instructions/*/Expected/*.txt')) as list($p, $e)) {
            $this->instructions[] = new FixtureInstruction($p, $e, $this);
        }
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private static function parseStaticFileContext(string $file): string
    {
        if (1 !== preg_match('{/(?<context>[^/]+)/data.json$}', $file, $match)) {
            throw new \RuntimeException(sprintf('Failed to parse context from fixture path "%s".', $file));
        }

        return static::transformCamelCaseToKebabCase($match['context']);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private static function readStaticJson(string $file): array
    {
        if (null === $data = json_decode(static::readFileContents($file), true)) {
            throw new \RuntimeException(sprintf('Failed to load fixture data from "%s".', $file));
        }

        return $data;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private static function readFileContents(string $file): string
    {
        if (false === $contents = file_get_contents($file)) {
            throw new \RuntimeException(sprintf('Failed to read file "%s".', $file));
        }

        return $contents;
    }
}
