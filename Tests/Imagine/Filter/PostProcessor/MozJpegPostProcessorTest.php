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

use Liip\ImagineBundle\Imagine\Filter\PostProcessor\MozJpegPostProcessor;
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Model\FileBinary;

/**
 * @covers \Liip\ImagineBundle\Imagine\Filter\PostProcessor\AbstractPostProcessor
 * @covers \Liip\ImagineBundle\Imagine\Filter\PostProcessor\MozJpegPostProcessor
 */
class MozJpegPostProcessorTest extends PostProcessorTestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation The %s::setQuality() method was deprecated in %s and will be removed in %s. You must setup the class state via its __construct() method. You can still pass filter-specific options to the process() method to overwrite behavior.
     */
    public function testDeprecatedSetQualityMethod()
    {
        $this->getPostProcessorInstance()->setQuality(50);
    }

    /**
     * @return mixed[]
     */
    public static function provideSetupProcessBuilderData()
    {
        $data = array(
            array(array(), array('-quant-table', 2, '-optimise')),
            array(array('quant_table' => 10), array('-quant-table', 10, '-optimise')),
            array(array('optimise' => false), array('-quant-table', 2)),
            array(array('optimise' => true), array('-quant-table', 2, '-optimise')),
            array(array('quality' => 50), array('-quant-table', 2, '-optimise', '-quality', 50)),
            array(array('quant_table' => 4, 'optimise' => true, 'quality' => 100), array('-quant-table', 4, '-optimise', '-quality', 100)),
        );

        return array_map(function (array $d) {
            array_unshift($d[1], AbstractPostProcessorTestCase::getPostProcessAsStdInExecutable());

            return $d;
        }, $data);
    }

    /**
     * @dataProvider provideSetupProcessBuilderData
     */
    public function testSetupProcessBuilder(array $options, array $expected)
    {
        $this->assertSame($expected, $this->getSetupProcessBuilderArguments($options));
    }

    /**
     * @return mixed[]
     */
    public static function provideProcessData()
    {
        $file = 'stdio-file-content-string';
        $data = array(
            array(array(), '-quant-table 2 -optimise'),
            array(array('quant_table' => 10), '-quant-table 10 -optimise'),
            array(array('optimise' => false), '-quant-table 2'),
            array(array('optimise' => true), '-quant-table 2 -optimise'),
            array(array('quality' => 50), '-quant-table 2 -optimise -quality 50'),
            array(array('quant_table' => 4, 'optimise' => true, 'quality' => 100), '-quant-table 4 -optimise -quality 100'),
        );

        return array_map(function ($d) use ($file) {
            array_unshift($d, $file);

            return $d;
        }, $data);
    }

    /**
     * @dataProvider provideProcessData
     *
     * @param string $content
     * @param array  $options
     * @param string $expected
     */
    public function testProcess($content, array $options, $expected)
    {
        $file = sys_get_temp_dir().'/test.jpeg';
        file_put_contents($file, $content);

        $process = $this->getPostProcessorInstance();
        $result = $process->process(new FileBinary($file, 'image/jpeg', 'jpeg'), $options);

        $this->assertContains($expected, $result->getContent());
        $this->assertContains($content, $result->getContent());

        @unlink($file);
    }

    /**
     * @dataProvider provideProcessData
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     *
     * @param array  $options
     * @param string $expected
     */
    public function testProcessError($content, array $options, $expected)
    {
        $process = $this->getPostProcessorInstance(array(static::getPostProcessAsStdInErrorExecutable()));
        $process->process(new Binary('content', 'image/jpeg', 'jpeg'), $options);
    }

    public function testProcessWithNonSupportedMimeType()
    {
        $binary = $this->getBinaryInterfaceMock();

        $binary
            ->expects($this->atLeastOnce())
            ->method('getMimeType')
            ->willReturn('application/x-php');

        $this->assertSame($binary, $this->getPostProcessorInstance()->process($binary, array()));
    }

    /**
     * @param array $parameters
     *
     * @return MozJpegPostProcessor
     */
    protected function getPostProcessorInstance(array $parameters = array())
    {
        return new MozJpegPostProcessor(isset($parameters[0]) ? $parameters[0] : static::getPostProcessAsStdinExecutable());
    }
}
