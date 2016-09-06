<?php

namespace Liip\ImagineBundle\Tests\Binary\Loader;

use Liip\ImagineBundle\Binary\Loader\ChainLoader;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;

/**
 * @covers \Liip\ImagineBundle\Binary\Loader\ChainLoader
 * @covers \Liip\ImagineBundle\Exception\Binary\Loader\NotChainLoadableException
 */
class ChainLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return LoaderInterface[]
     */
    public static function getChainLoaderInstances($fileSystemRoot = __DIR__, $fileSystemStream = 'file://')
    {
        return array(
            FileSystemLoaderTest::createLoaderInstance($fileSystemRoot),
            StreamLoaderTest::createLoaderInstance($fileSystemStream),
        );
    }

    /**
     * @return ChainLoader
     */
    public static function createLoaderInstance($loaders = array())
    {
        return new ChainLoader($loaders);
    }

    /**
     * @return ChainLoader
     */
    public static function createLoaderInstancePopulated()
    {
        return static::createLoaderInstance(static::getChainLoaderInstances());
    }

    /**
     * @return string[]
     */
    public static function provideLoadCases()
    {
        $fileName = pathinfo(__FILE__, PATHINFO_BASENAME);

        return array(
            array($fileName),
            array($fileName),
            array('/'.$fileName),
            array('/'.$fileName),
        );
    }

    /**
     * @return string[]
     */
    public static function provideErrorLoadCases()
    {
        $fileName = pathinfo(__FILE__, PATHINFO_BASENAME);

        return array(
            array('/../../'.$fileName),
        );
    }

    public function testShouldImplementLoaderInterface()
    {
        $rc = new \ReflectionClass('Liip\ImagineBundle\Binary\Loader\ChainLoader');

        $this->assertTrue($rc->implementsInterface('Liip\ImagineBundle\Binary\Loader\ChainLoaderInterface'));
        $this->assertTrue($rc->implementsInterface('Liip\ImagineBundle\Binary\Loader\LoaderInterface'));
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        static::createLoaderInstance();
    }

    public function testThrowExceptionIfFileNotExist()
    {
        $loader = static::createLoaderInstancePopulated();

        $this->setExpectedException(
            'Liip\ImagineBundle\Exception\Binary\Loader\NotChainLoadableException',
            'Source image not found'
        );

        $loader->find('fileNotExist');
    }

    /**
     * @dataProvider provideLoadCases
     */
    public function testLoad($path)
    {
        $loader = static::createLoaderInstancePopulated();
        $binary = $loader->find($path);

        $this->assertInstanceOf('Liip\ImagineBundle\Model\FileBinary', $binary);
        $this->assertStringStartsWith('text/', $binary->getMimeType());
    }

    /**
     * @dataProvider provideErrorLoadCases
     */
    public function testNotLoadable($path)
    {
        try {
            static::createLoaderInstancePopulated()->find($path);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Liip\ImagineBundle\Exception\Binary\Loader\NotChainLoadableException', $e);

            return;
        }

        $this->fail(sprintf('Failed on path "%s"', $path));
    }

    /**
     * @expectedException \Liip\ImagineBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid chain loader added
     */
    public function testThrowsExceptionOnNonChainableLoadersAdded()
    {
        $mockLoader = $this
            ->getMockBuilder('Liip\ImagineBundle\Binary\Loader\LoaderInterface')
            ->getMock();

        static::createLoaderInstance(array(
            $mockLoader,
        ));
    }
}
