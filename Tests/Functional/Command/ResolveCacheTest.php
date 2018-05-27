<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Functional\Command;

/**
 * @covers \Liip\ImagineBundle\Command\ResolveCacheCommand
 */
class ResolveCacheTest extends AbstractCommandTestCase
{
    public function testShouldResolveWithEmptyCache()
    {
        $this->assertFileNotExists($this->cacheRoot.'/thumbnail_web_path/images/cats.jpg');

        $output = $this->executeConsole(
            $this->getService('liip_imagine.command.resolve_cache_command'),
            [
                'paths' => ['images/cats.jpg'],
                '--filters' => ['thumbnail_web_path'], ]
        );

        $this->assertFileExists($this->cacheRoot.'/thumbnail_web_path/images/cats.jpg');
        $this->assertFileNotExists($this->cacheRoot.'/thumbnail_default/images/cats.jpg');
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.jpg', $output);
    }

    public function testShouldResolveWithCacheExists()
    {
        $this->filesystem->dumpFile(
            $this->cacheRoot.'/thumbnail_web_path/images/cats.jpg',
            'anImageContent'
        );

        $output = $this->executeConsole(
            $this->getService('liip_imagine.command.resolve_cache_command'),
            [
                'paths' => ['images/cats.jpg'],
                '--filters' => ['thumbnail_web_path'], ]
        );

        $this->assertFileExists($this->cacheRoot.'/thumbnail_web_path/images/cats.jpg');
        $this->assertFileNotExists($this->cacheRoot.'/thumbnail_default/images/cats.jpg');
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.jpg', $output);
    }

    public function testShouldResolveWithFewPathsAndSingleFilter()
    {
        $output = $this->executeConsole(
            $this->getService('liip_imagine.command.resolve_cache_command'),
            [
                'paths' => ['images/cats.jpg', 'images/cats.png'],
                '--filters' => ['thumbnail_web_path'], ]
        );

        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.jpg', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.png', $output);
    }

    public function testShouldResolveWithFewPathsSingleFilterAndPartiallyFullCache()
    {
        $this->assertFileNotExists($this->cacheRoot.'/thumbnail_web_path/images/cats.jpg');

        $this->filesystem->dumpFile(
            $this->cacheRoot.'/thumbnail_web_path/images/cats.png',
            'anImageContent'
        );

        $output = $this->executeConsole(
            $this->getService('liip_imagine.command.resolve_cache_command'),
            [
                'paths' => ['images/cats.jpg', 'images/cats.png'],
                '--filters' => ['thumbnail_web_path'], ]
        );

        $this->assertFileNotExists($this->cacheRoot.'/thumbnail_default/images/cats.jpg');
        $this->assertFileExists($this->cacheRoot.'/thumbnail_web_path/images/cats.jpg');
        $this->assertFileExists($this->cacheRoot.'/thumbnail_web_path/images/cats.png');
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.jpg', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.png', $output);
    }

    public function testShouldResolveWithFewPathsAndFewFilters()
    {
        $output = $this->executeConsole(
            $this->getService('liip_imagine.command.resolve_cache_command'),
            [
                'paths' => ['images/cats.jpg', 'images/cats.png'],
                '--filters' => ['thumbnail_web_path', 'thumbnail_default'], ]
        );

        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.jpg', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.png', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_default/images/cats.jpg', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_default/images/cats.png', $output);
    }

    public function testShouldResolveWithFewPathsAndWithoutFilters()
    {
        $output = $this->executeConsole(
            $this->getService('liip_imagine.command.resolve_cache_command'),
            ['paths' => ['images/cats.jpg', 'images/cats.png']]
        );

        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.jpg', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_web_path/images/cats.png', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_default/images/cats.jpg', $output);
        $this->assertContains('http://localhost/media/cache/thumbnail_default/images/cats.png', $output);
    }
}
