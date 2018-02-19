<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests;

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Binary\MimeTypeGuesserInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Liip\ImagineBundle\Imagine\Cache\SignerInterface;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Imagine\Filter\PostProcessor\PostProcessorInterface;
use Liip\ImagineBundle\Service\FilterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractTest extends TestCase
{
    /**
     * @var string
     */
    private $fixturePath;

    /**
     * @var string
     */
    private $tmpWorkPath;

    protected function setUp(): void
    {
        $this->fixturePath = realpath(__DIR__.'/Fixtures');
        $this->tmpWorkPath = sprintf('%s/liip_imagine_test', sys_get_temp_dir());

        $this->emptyTemporaryWorkPath();
    }

    protected function tearDown(): void
    {
        $this->emptyTemporaryWorkPath(false);
    }

    /**
     * @return string
     */
    protected function getFixturePath(): string
    {
        return $this->fixturePath;
    }

    /**
     * @return string
     */
    protected function getTmpWorkPath(): string
    {
        return $this->tmpWorkPath;
    }

    /**
     * @return \Iterator
     */
    public function invalidPathProvider(): \Iterator
    {
        yield [$this->getFixturePath().'/assets/../../foobar.png'];
        yield [$this->getFixturePath().'/assets/some_folder/../foobar.png'];
        yield ['../../outside/foobar.jpg'];
    }

    /**
     * @param string[] $filterNames
     * @param array[]  $filterConfs
     *
     * @return FilterConfiguration
     */
    protected function createFilterConfiguration(array $filterNames = [], array $filterConfs = []): FilterConfiguration
    {
        $d = ['thumbnail' => ['size' => [180, 180], 'mode' => 'outbound']];
        $c = new FilterConfiguration($filterNames);

        foreach ($filterConfs ?? $d as $name => $conf) {
            $c->set($name, $conf);
        }

        return $c;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheManager
     */
    protected function createCacheManagerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(CacheManager::class, null, true, [
            $this->createFilterConfiguration(),
            $this->createRouterInterfaceMock(),
            $this->createSignerInterfaceMock(),
            $this->createEventDispatcherInterfaceMock(),
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterConfiguration
     */
    protected function createFilterConfigurationMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(FilterConfiguration::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SignerInterface
     */
    protected function createSignerInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(SignerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RouterInterface
     */
    protected function createRouterInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(RouterInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ResolverInterface
     */
    protected function createCacheResolverInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(ResolverInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected function createEventDispatcherInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(EventDispatcherInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ImageInterface
     */
    protected function getImageInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(ImageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MetadataBag
     */
    protected function getMetadataBagMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(MetadataBag::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ImagineInterface
     */
    protected function createImagineInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(ImagineInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoaderInterface
     */
    protected function createBinaryLoaderInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(LoaderInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MimeTypeGuesserInterface
     */
    protected function createMimeTypeGuesserInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(MimeTypeGuesserInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionGuesserInterface
     */
    protected function createExtensionGuesserInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(ExtensionGuesserInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PostProcessorInterface
     */
    protected function createPostProcessorInterfaceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(PostProcessorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterManager
     */
    protected function createFilterManagerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(FilterManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterService
     */
    protected function createFilterServiceMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(FilterService::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DataManager
     */
    protected function createDataManagerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createObjectMock(DataManager::class);
    }

    /**
     * @param string        $object
     * @param string[]|null $methods
     * @param bool          $constructorEnabled
     * @param mixed[]       $constructorArgs
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createObjectMock(string $object, array $methods = null, bool $constructorEnabled = false, array $constructorArgs = []): \PHPUnit_Framework_MockObject_MockObject
    {
        $builder = $this->getMockBuilder($object);

        if (!empty($methods)) {
            $builder->setMethods($methods);
        }

        if ($constructorEnabled) {
            $builder->enableOriginalConstructor();
        } else {
            $builder->disableOriginalConstructor();
        }

        if (!empty($constructorArgs)) {
            $builder->setConstructorArgs($constructorArgs);
        }

        return $builder->getMock();
    }

    /**
     * @param \ReflectionObject|string $object
     * @param string                   $method
     *
     * @return \ReflectionMethod
     */
    protected function getAccessiblePrivateMethod($object, string $method)
    {
        $m = $this->getReflectionObjectInstanceForObject($object)->getMethod($method);
        $m->setAccessible(true);

        return $m;
    }

    /**
     * @param \ReflectionObject|string $object
     * @param string                   $property
     *
     * @return \ReflectionProperty
     */
    protected function getAccessiblePrivateProperty($object, string $property)
    {
        $p = $this->getReflectionObjectInstanceForObject($object)->getProperty($property);
        $p->setAccessible(true);

        return $p;
    }

    /**
     * @param string|\ReflectionObject $object
     *
     * @return \ReflectionObject
     */
    private function getReflectionObjectInstanceForObject($object): \ReflectionObject
    {
        try {
            return false === $object instanceof \ReflectionObject ? new \ReflectionObject($object) : $object;
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                sprintf('Unable to create reflection object for "%s"', var_export($object, true)), 0, $e
            );
        }
    }

    /**
     * @param bool $recreate
     *
     * @return Filesystem
     */
    protected function getFileSystemInstance(bool $recreate = false): Filesystem
    {
        static $filesystem;

        if (null === $filesystem || true === $recreate) {
            $filesystem = new FileSystem();
        }

        return $filesystem;
    }

    /**
     * @param bool $recreate
     */
    protected function emptyTemporaryWorkPath(bool $recreate = true): void
    {
        $filesystem = $this->getFileSystemInstance();
        $tmpWorkDir = $this->getTmpWorkPath();

        if ($filesystem->exists($tmpWorkDir)) {
            $filesystem->remove($tmpWorkDir);
        }

        if (true === $recreate) {
            $filesystem->mkdir($tmpWorkDir);
        }
    }
}
