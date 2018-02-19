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
use Liip\ImagineBundle\Utility\Process\DescribeProcess;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * @covers \Liip\ImagineBundle\Imagine\Filter\PostProcessor\AbstractPostProcessor
 */
class AbstractPostProcessorTest extends PostProcessorTestCase
{
   public function testIsBinaryOfType()
    {
        $binary = $this->getBinaryInterfaceMock();

        $binary
            ->expects($this->atLeastOnce())
            ->method('getMimeType')
            ->willReturnOnConsecutiveCalls(
                'image/jpg', 'image/jpeg', 'text/plain', 'image/png', 'image/jpg', 'image/jpeg', 'text/plain', 'image/png'
            );

        $processor = $this->getPostProcessorInstance();

        $m = $this->getAccessiblePrivateMethod($processor, 'isBinaryTypeJpgImage');
        $this->assertTrue($m->invoke($processor, $binary));
        $this->assertTrue($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));

        $m = $this->getAccessiblePrivateMethod($processor, 'isBinaryTypePngImage');
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertFalse($m->invoke($processor, $binary));
        $this->assertTrue($m->invoke($processor, $binary));
    }

    public function testCreateProcessBuilder()
    {
        $optionStrip = true;
        $optionQuality = 80;
        $optionPrefix = ['a-custom-prefix'];
        $optionWorkDir = getcwd();
        $optionEnvVars = ['FOO' => 'BAR'];
        $optionOptions = ['bypass_shell' => true];
        $extraArgument = 'extra-argument';

        $c = function (DescribeProcess $d, array $options) {
            $d->mergeOptions($options);
            $d->pushArgument('--strip=%s', $options['strip'] ? 'yes' : 'no');
            $d->pushArgument('--quality=%d', $options['quality']);
            $d->pushArgument('closure-called');
        };
        $p = $this->getPostProcessorInstance(['/path/to/bin']);
        $m = $this->getAccessiblePrivateMethod($p, 'createProcess');

        /** @var Process $b */
        $b = $m->invoke($p, [
            'strip' => $optionStrip,
            'quality' => $optionQuality,
            'process' => [
                'environment_variables' => $optionEnvVars,
                'working_directory' => $optionWorkDir,
                'prefix' => $optionPrefix,
            ]
        ], $c, $extraArgument);

        $this->assertContains('closure-called', $b->getCommandLine());

        return;
        $b = $m->invoke($p, [
            'process' => [
                'timeout' => $optionTimeout,
                'prefix' => $optionPrefix,
                'working_directory' => $optionWorkDir,
                'environment_variables' => $optionEnvVars,
                'options' => $optionOptions,
            ],
        ]);

        $this->assertSame($optionTimeout, $this->getAccessiblePrivateProperty($b, 'timeout')->getValue($b));
        $this->assertSame($optionPrefix, $this->getAccessiblePrivateProperty($b, 'prefix')->getValue($b));
        $this->assertSame($optionWorkDir, $this->getAccessiblePrivateProperty($b, 'cwd')->getValue($b));
        $this->assertSame($optionEnvVars, $this->getAccessiblePrivateProperty($b, 'env')->getValue($b));
        $this->assertSame($optionOptions, $this->getAccessiblePrivateProperty($b, 'options')->getValue($b));
    }

    /**
     * @return array[]
     */
    public static function provideWriteTemporaryFileData()
    {
        $find = new Finder();
        $data = [];

        foreach ($find->in(__DIR__)->name('*.php')->files() as $f) {
            $data[] = [file_get_contents($f), 'application/x-php', 'php', 'foo-context', []];
            $data[] = [file_get_contents($f), 'application/x-php', 'php', 'bar-context', ['temp_dir' => null]];
            $data[] = [file_get_contents($f), 'application/x-php', 'php', 'bar-context', ['temp_dir' => sys_get_temp_dir()]];
            $data[] = [file_get_contents($f), 'application/x-php', 'php', 'baz-context', ['temp_dir' => sprintf('%s/foo/bar/baz', sys_get_temp_dir())]];
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
        $writer = $this->getAccessiblePrivateMethod($processor = $this->getPostProcessorInstance(), 'writeTemporaryFile');

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
     * @return \Iterator
     */
    public static function provideIsValidReturnData(): \Iterator
    {
        yield [[], [], true];
        yield [[0], [], true];
        yield [[100, 200, 0], [], true];
        yield [[100], [], false];
        yield [[100, 200], [], false];
        yield [[], ['*ERROR*'], true];
        yield [[], ['*ERROR*'], true, true];
        yield [[], ['*foo*'], false];
        yield [[], ['*foo*'], false, true];
        yield [[], ['*foo-bar*', '*baz*'], false];
        yield [[0], ['*foo-bar*', '*baz*'], false];
        yield [[0, 1], ['*foo-bar*', '*baz*'], false];
        yield [[1, 2], ['*foo-bar*', '*baz*'], false];
        yield [[], ['*foo-bar*', '*baz*'], true, true];
        yield [[0], ['*foo-bar*', '*baz*'], true, true];
        yield [[0, 1], ['*foo-bar*', '*baz*'], true, true];
        yield [[1, 2], ['*foo-bar*', '*baz*'], false, true];
        yield [[], ['*foo-bar*', '*ERROR*'], true];
        yield [[0], ['*foo-bar*', '*ERROR*'], true];
        yield [[0, 1], ['*foo-bar*', '*ERROR*'], true];
        yield [[1, 2], ['*foo-bar*', '*ERROR*'], false];
        yield [[], ['*foo-bar*', '*ERROR*'], true, true];
        yield [[0], ['*foo-bar*', '*ERROR*'], true, true];
        yield [[0, 1], ['*foo-bar*', '*ERROR*'], true, true];
        yield [[1, 2], ['*foo-bar*', '*ERROR*'], false, true];
    }

    /**
     * @dataProvider provideIsValidReturnData
     *
     * @param array $validReturns
     * @param array $errorString
     * @param bool  $expected
     */
    public function testIsValidReturn(array $validReturns, array $errorString, $expected, bool $requireAllErrors = false)
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
            ->getAccessiblePrivateMethod($processor = $this->getPostProcessorInstance(), 'isProcessSuccess')
            ->invoke($processor, $process, $validReturns, $errorString, $requireAllErrors);

        $this->assertSame($expected, $result);
    }

    /**
     * @param array    $parameters
     * @param string[] $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractPostProcessor
     */
    protected function getPostProcessorInstance(array $parameters = [], array $methods = [])
    {
        if (count($parameters) === 0) {
            $parameters = [static::getPostProcessAsStdInExecutable()];
        }

        return $this
            ->getMockBuilder('\Liip\ImagineBundle\Imagine\Filter\PostProcessor\AbstractPostProcessor')
            ->setMethods($methods)
            ->setConstructorArgs($parameters)
            ->getMockForAbstractClass();
    }
}
