<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Imagine\Filter\PostProcessor;

use Liip\ImagineBundle\Imagine\Filter\PostProcessor\AbstractPostProcessor;
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Model\FileBinary;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @covers \Liip\ImagineBundle\Imagine\Filter\PostProcessor\AbstractPostProcessor
 */
class AbstractPostProcessorTest extends AbstractPostProcessorTestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation The processWithConfiguration() method was deprecated in %s and will be removed in %s in favor of the %s::process() method, which was expanded to allow passing options as the second argument.
     */
    public function testProcessWithConfigurationDeprecation()
    {
        $this
            ->getProtectedReflectionMethodVisible($processor = $this->getPostProcessorInstance(), 'processWithConfiguration')
            ->invoke($processor, $this->getBinaryInterfaceMock(), array());
    }

    public function testIsBinaryMimeType()
    {
        $binary = $this->getBinaryInterfaceMock();

        $binary
            ->expects($this->atLeastOnce())
            ->method('getMimeType')
            ->willReturnOnConsecutiveCalls(
                'image/jpg', 'image/jpeg', 'text/plain', 'image/png', 'image/jpg', 'image/jpeg', 'text/plain', 'image/png'
            );

        $processor = $this->getPostProcessorInstance();

        $m = $this->getProtectedReflectionMethodVisible($processor, 'isBinaryJpgMimeType');
        $this->assertTrue($m->invoke($processor, $binary));
        $this->assertTrue($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));

        $m = $this->getProtectedReflectionMethodVisible($processor, 'isBinaryPngMimeType');
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertTrue($m->invoke($processor, $binary));
    }

    /**
     * @return array[]
     */
    public static function provideCreateProcessBuilderData()
    {
        $root = realpath(__DIR__.'/../../../');

        $getBin = function (\SplFileInfo $path) use ($root) {
            $finder = new Finder();
            $finder->in($path->getRealPath());

            $files = iterator_to_array($finder->files(), false);
            shuffle($files);
            $executable = array_pop($files);

            return $executable instanceof \SplFileInfo ? $executable->getRealPath() : null;
        };

        $getChars = function($onlyAlpha = true) {
            $chars = '';
            for ($i = 0; $i < mt_rand(1, 80); $i++) {
                $chars .= chr($onlyAlpha ? mt_rand(97, 122) : mt_rand(35, 127));
            }

            return strtolower($chars);
        };

        $getEnvVars = function() use ($getChars) {
            $environment = array();
            for ($i = 0; $i < mt_rand(0, 20); $i++) {
                $environment[strtoupper($getChars())] = $getChars(false);
            }

            return $environment;
        };

        $finder = new Finder();
        $finder->in($root);
        $directories = iterator_to_array($finder->directories(), false);
        shuffle($directories);
        $returns = array();

        foreach (array_splice($directories, 0, 20) as $d) {
            $returns[] = array($d->getRealPath(), $getBin($d), (float) sprintf('%d.%d', mt_rand(0, 300), mt_rand(0, 99)),
                $getChars(), $getEnvVars(), mt_rand(0, 1) === 0 ? array('bypass_shell' => true) : array());
        }

        return array_filter($returns, function (array $d) {
            return $d[1] !== null;
        });
    }

    /**
     * @dataProvider provideCreateProcessBuilderData
     *
     * @param string  $workingDirectory
     * @param string  $executablePath
     * @param int     $timeout
     * @param string  $prefix
     * @param mixed[] $environmentVariables
     * @param mixed[] $options
     */
    public function testCreateProcessBuilder($workingDirectory, $executablePath, $timeout, $prefix, array $environmentVariables, array $options)
    {
        $b = $this->callCreateProcessBuilder(array(
            'process' => array(
                'working_directory' => $workingDirectory,
                'timeout' => $timeout,
                'prefix' => $prefix,
                'environment_variables' => $environmentVariables,
                'options' => $options,
            ),
        ), $executablePath);

        $this->assertSame($timeout, $this->getProtectedReflectionPropertyVisible($b, 'timeout')->getValue($b));
        $this->assertSame(array($prefix), $this->getProtectedReflectionPropertyVisible($b, 'prefix')->getValue($b));
        $this->assertSame($workingDirectory, $this->getProtectedReflectionPropertyVisible($b, 'cwd')->getValue($b));
        $this->assertSame($environmentVariables, $this->getProtectedReflectionPropertyVisible($b, 'env')->getValue($b));
        $this->assertSame($options, $this->getProtectedReflectionPropertyVisible($b, 'options')->getValue($b));
        $this->assertSame(array($executablePath), $this->getProtectedReflectionPropertyVisible($b, 'arguments')->getValue($b));
    }

    /**
     * @return array
     */
    public static function provideCreateProcessBuilderThrowsOnInvalidEnvVarsData()
    {
        return array(
            array(true),
            array(false),
            array(-9999),
            array(0),
            array(10000),
            array(34.54),
            array('a-string'),
            array(new \stdClass()),
        );
    }

    /**
     * @dataProvider provideCreateProcessBuilderThrowsOnInvalidEnvVarsData
     *
     * @expectedException \Liip\ImagineBundle\Exception\Imagine\Filter\PostProcessor\InvalidOptionException
     * @expectedExceptionMessage the "process:environment_variables" option must be an array of name => value pairs
     */
    public function testCreateProcessBuilderThrowsOnInvalidEnvVars($environmentVariables)
    {
        $this->callCreateProcessBuilder(array(
            'process' => array(
                'environment_variables' => $environmentVariables,
            ),
        ), '/bin/foobar');
    }
    /**
     * @return array
     */
    public static function provideCreateProcessBuilderThrowsOnInvalidOptionsData()
    {
        return static::provideCreateProcessBuilderThrowsOnInvalidEnvVarsData();
    }

    /**
     * @dataProvider provideCreateProcessBuilderThrowsOnInvalidOptionsData
     *
     * @expectedException \Liip\ImagineBundle\Exception\Imagine\Filter\PostProcessor\InvalidOptionException
     * @expectedExceptionMessage the "process:options" option must be an array of options intended for the proc_open function call
     */
    public function testCreateProcessBuilderThrowsOnInvalidOptions($options)
    {
        $this->callCreateProcessBuilder(array(
            'process' => array(
                'options' => $options,
            ),
        ), '/bin/foobar');
    }

    /**
     * @param array  $options
     * @param string $executablePath
     *
     * @return ProcessBuilder
     */
    private function callCreateProcessBuilder(array $options, $executablePath)
    {
        $m = $this->getProtectedReflectionMethodVisible($processor = $this->getPostProcessorInstance(), 'createProcessBuilder');

        return $m->invokeArgs($processor, array($options, array($executablePath)));
    }

    public function testCreateProcessBuilderWithDefaultExecutable()
    {
        $executablePath = '/bin/foobar';

        $p = $this->getProtectedReflectionPropertyVisible($processor = $this->getPostProcessorInstance(), 'executablePath');
        $p->setValue($processor, $executablePath);

        $m = $this->getProtectedReflectionMethodVisible($this->getPostProcessorInstance(), 'createProcessBuilder');
        $b = $m->invoke($processor);

        $this->assertSame(array($executablePath), $this->getProtectedReflectionPropertyVisible($b, 'arguments')->getValue($b));
    }

    /**
     * @return array[]
     */
    public static function provideWriteTemporaryFileData()
    {
        $find = new Finder();
        $data = array();

        foreach ($find->in(__DIR__)->name('*.php')->files() as $f) {
            $data[] = array(file_get_contents($f), 'application/x-php', 'php', 'foo-context', array());
            $data[] = array(file_get_contents($f), 'application/x-php', 'php', 'bar-context', array('temp_dir' => null));
            $data[] = array(file_get_contents($f), 'application/x-php', 'php', 'bar-context', array('temp_dir' => sys_get_temp_dir()));
            $data[] = array(file_get_contents($f), 'application/x-php', 'php', 'baz-context', array('temp_dir' => sprintf('%s/foo/bar/baz', sys_get_temp_dir())));
        }

        return $data;
    }

    /**
     * @dataProvider provideWriteTemporaryFileData
     *
     * @param string $content
     * @param string $mimeType
     * @param string $format
     * @param string $prefix
     * @param array  $options
     */
    public function testWriteTemporaryFile($content, $mimeType, $format, $prefix, array $options)
    {
        $writer = $this->getProtectedReflectionMethodVisible($processor = $this->getPostProcessorInstance(), 'writeTemporaryFile');

        $baseBinary = new Binary($content, $mimeType, $format);
        $this->assertTemporaryFile($content, $base = $writer->invoke($processor, $baseBinary, $options, $prefix), $prefix, $options);

        $fileBinary = new FileBinary($base, $mimeType, $format);
        $this->assertTemporaryFile($content, $file = $writer->invoke($processor, $fileBinary, $options, $prefix), $prefix, $options);

        @unlink($base);
        @unlink($file);

        if (is_dir($dir = sprintf('%s/foo/bar/baz', sys_get_temp_dir()))) {
            @rmdir($dir);
        }

        if (is_dir($dir = sprintf('%s/foo/bar', sys_get_temp_dir()))) {
            @rmdir($dir);
        }

        if (is_dir($dir = sprintf('%s/foo', sys_get_temp_dir()))) {
            @rmdir($dir);
        }
    }

    /**
     * @return array[]
     */
    public static function provideIsSuccessfulProcess()
    {
        return array(
            array(array(), array(), true),
            array(array(0), array(), true),
            array(array(100, 200, 0), array(), true),
            array(array(100), array(), false),
            array(array(100, 200), array(), false),
            array(array(), array('ERROR'), true),
            array(array(0), array('foo'), false),
            array(array(0), array('foo-bar', 'baz'), false),
            array(array(0), array('foo-bar', 'ERROR'), true),
        );
    }

    /**
     * @dataProvider provideIsSuccessfulProcess
     *
     * @param array $validReturns
     * @param array $errorString
     * @param bool  $expected
     */
    public function testIsSuccessfulProcess(array $validReturns, array $errorString, $expected)
    {
        $process = $this
            ->getMockBuilder('\Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process
            ->expects($this->any())
            ->method('getExitCode')
            ->willReturn(0);

        $process
            ->expects($this->any())
            ->method('getOutput')
            ->willReturn('foo bar baz');

        $result = $this
            ->getProtectedReflectionMethodVisible($processor = $this->getPostProcessorInstance(), 'isProcessSuccessful')
            ->invoke($processor, $process, $validReturns, $errorString);

        $this->assertSame($expected, $result);
    }

    /**
     * @param array $parameters
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractPostProcessor
     */
    protected function getPostProcessorInstance(array $parameters = array())
    {
        if (count($parameters) === 0) {
            $parameters = array(static::getPostProcessAsStdInExecutable());
        }

        return $this
            ->getMockBuilder('\Liip\ImagineBundle\Imagine\Filter\PostProcessor\AbstractPostProcessor')
            ->setConstructorArgs($parameters)
            ->getMockForAbstractClass();
    }
}
