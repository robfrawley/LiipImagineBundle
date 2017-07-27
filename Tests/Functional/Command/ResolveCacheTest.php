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

use Liip\ImagineBundle\Command\ResolveCacheCommand;

/**
 * @covers \Liip\ImagineBundle\Command\ResolveCacheCommand
 */
class ResolveCacheTest extends AbstractCommandTestCase
{
    public function testShouldResolveWithEmptyCache()
    {
        $images = array('images/cats.jpeg', 'images/cats2.jpeg');
        $filters = array('thumbnail_web_path');

        $this->assertImagesNotExist($images, $filters);

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertImagesNotExist($images, array('thumbnail_default'));
        $this->assertOutputContainsResolvedImages($output, $images, $filters);

        $this->delResolvedImages($images, $filters);
    }

    public function testShouldResolveWithCacheExists()
    {
        $images = array('images/cats.jpeg');
        $filters = array('thumbnail_web_path');

        $this->putResolvedImages($images, $filters);

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertImagesNotExist($images, array('thumbnail_default'));
        $this->assertOutputContainsCachedImages($output, $images, $filters);

        $this->delResolvedImages($images, $filters);
    }

    public function testShouldResolveWithFewPathsAndSingleFilter()
    {
        $images = array('images/cats.jpeg', 'images/cats2.jpeg');
        $filters = array('thumbnail_web_path');

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsResolvedImages($output, $images, $filters);

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsCachedImages($output, $images, $filters);

        $this->delResolvedImages($images, $filters);
    }

    public function testShouldResolveWithFewPathsSingleFilterAndPartiallyFullCache()
    {
        $imagesResolved = array('images/cats.jpeg');
        $imagesCached = array('images/cats2.jpeg');
        $images = array_merge($imagesResolved, $imagesCached);
        $filters = array('thumbnail_web_path');

        $this->putResolvedImages($imagesCached, $filters);

        $this->assertImagesNotExist($imagesResolved, $filters);
        $this->assertImagesExist($imagesCached, $filters);

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsResolvedImages($output, $imagesResolved, $filters);
        $this->assertOutputContainsCachedImages($output, $imagesCached, $filters);

        $this->delResolvedImages($images, $filters);
    }

    public function testShouldResolveWithFewPathsAndFewFilters()
    {
        $images = array('images/cats.jpeg', 'images/cats2.jpeg');
        $filters = array('thumbnail_web_path', 'thumbnail_default');

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsResolvedImages($output, $images, $filters);

        $this->delResolvedImages($images, $filters);
    }

    public function testShouldResolveWithFewPathsAndWithoutFilters()
    {
        $images = array('images/cats.jpeg', 'images/cats2.jpeg');
        $filters = array('thumbnail_web_path', 'thumbnail_default');

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsResolvedImages($output, $images, $filters);

        $this->delResolvedImages($images, $filters);
    }

    public function testCachedAndForceResolve()
    {
        $images = array('images/cats.jpeg', 'images/cats2.jpeg');
        $filters = array('thumbnail_web_path', 'thumbnail_default');

        $this->assertImagesNotExist($images, $filters);
        $this->putResolvedImages($images, $filters);
        $this->assertImagesExist($images, $filters);

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsCachedImages($output, $images, $filters);

        $output = $this->executeConsole(new ResolveCacheCommand(), array('paths' => $images, '--filters' => $filters, '--force' => true));

        $this->assertImagesExist($images, $filters);
        $this->assertOutputContainsResolvedImages($output, $images, $filters);

        $this->delResolvedImages($images, $filters);
    }

    /**
     * @param string[] $images
     * @param string[] $filters
     */
    private function assertImagesNotExist($images, $filters)
    {
        foreach ($images as $i) {
            foreach ($filters as $f) {
                $this->assertFileNotExists(sprintf('%s/%s/%s', $this->cacheRoot, $f, $i));
            }
        }
    }

    /**
     * @param string[] $images
     * @param string[] $filters
     */
    private function assertImagesExist($images, $filters)
    {
        foreach ($images as $i) {
            foreach ($filters as $f) {
                $this->assertFileExists(sprintf('%s/%s/%s', $this->cacheRoot, $f, $i));
            }
        }
    }

    /**
     * @param string $output
     * @param array  $images
     * @param array  $filters
     */
    private function assertOutputContainsResolvedImages($output, array $images, array $filters)
    {
        foreach ($images as $i) {
            foreach ($filters as $f) {
                $this->assertOutputContainsImage($output, $i, $f, 'RESOLVED');
            }
        }
    }

    /**
     * @param string $output
     * @param array  $images
     * @param array  $filters
     */
    private function assertOutputContainsCachedImages($output, array $images, array $filters)
    {
        foreach ($images as $i) {
            foreach ($filters as $f) {
                $this->assertOutputContainsImage($output, $i, $f, 'CACHED');
            }
        }
    }

    /**
     * @param string $output
     * @param string $image
     * @param string $filter
     * @param string $type
     */
    private function assertOutputContainsImage($output, $image, $filter, $type)
    {
        $expected = vsprintf('"%s[%s]" %s as "http://localhost/media/cache/%s/%s"', [
            $image,
            $filter,
            $type,
            $filter,
            $image,
        ]);
        $this->assertContains($expected, $output);
    }

    /**
     * @param string[] $images
     * @param string[] $filters
     */
    private function delResolvedImages(array $images, array $filters)
    {
        foreach ($images as $i) {
            foreach ($filters as $f) {
                if (file_exists($f = sprintf('%s/%s/%s', $this->cacheRoot, $f, $i))) {
                    @unlink($f);
                }
            }
        }
    }

    /**
     * @param string[] $images
     * @param string[] $filters
     * @param string   $content
     */
    private function putResolvedImages(array $images, array $filters, $content = 'anImageContent')
    {
        foreach ($images as $i) {
            foreach ($filters as $f) {
                $this->filesystem->dumpFile(sprintf('%s/%s/%s', $this->cacheRoot, $f, $i), $content);
            }
        }
    }
}
