<?php

/*
 * This file is part of the `src-run/augustus-utility-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Utility\Output\Buffer;

use Liip\ImagineBundle\Exception\Utility\Output\Buffer\OutputException;
use Liip\ImagineBundle\Tests\Utility\Filesystem\TemporaryFileTest;
use Liip\ImagineBundle\Utility\Output\Buffer\OutputBuffer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Liip\ImagineBundle\Utility\Output\Buffer\OutputBuffer
 */
class OutputBufferTest extends TestCase
{
    /**
     * @return \Iterator
     */
    public static function provideMaximumSizesAndSchemeFormat(): \Iterator
    {
        yield [null, null, 'php://memory'];
        yield [1, 1000000, 'php://temp/maxmemory:%d'];
        yield [10, 10000000, 'php://temp/maxmemory:%d'];
        yield [2000, 2000000000, 'php://temp/maxmemory:%d'];
        yield [null, null, 'php://memory'];
    }

    /**
     * @dataProvider provideMaximumSizesAndSchemeFormat
     *
     * @param int|null $megabytes
     * @param int|null $bytes
     * @param string   $scheme
     */
    public function testConstruction(int $megabytes = null, int $bytes = null, string $scheme): void
    {
        $this->assertStringMatchesFormat($scheme, (new OutputBuffer($megabytes))->getScheme());

        if (null === $megabytes) {
            $this->assertStringMatchesFormat($scheme, OutputBuffer::createBufferMemoryOnly('foobar')->getScheme());
        } else {
            $this->assertStringMatchesFormat($scheme, OutputBuffer::createBufferMemoryLimited('foobar', $megabytes)->getScheme());
        }
    }

    /**
     * @dataProvider provideMaximumSizesAndSchemeFormat
     *
     * @param int|null $megabytes
     * @param int|null $bytes
     * @param string   $scheme
     */
    public function testScheme(int $megabytes = null, int $bytes = null, string $scheme): void
    {
        $this->assertSame(sprintf($scheme, $bytes), (new OutputBuffer($megabytes))->getScheme());
    }

    /**
     * @dataProvider provideMaximumSizesAndSchemeFormat
     *
     * @param int|null $megabytes
     * @param int|null $bytes
     */
    public function testBytes(int $megabytes = null, int $bytes = null): void
    {
        $buffer = new OutputBuffer($megabytes);

        $this->assertSame($bytes, $buffer->getMaximumBytes());
        $this->assertSame(0, $buffer->getUsedBytes());
        $this->assertSame($bytes, $buffer->getAvailableBytes());

        if (null === $megabytes) {
            $this->assertFalse($buffer->hasMaximumBytes());
        } else {
            $this->assertTrue($buffer->hasMaximumBytes());
        }
    }

    /**
     * @return \Iterator
     */
    public static function provideFileData(): \Iterator
    {
        $files = TemporaryFileTest::getRandomFiles(6, true);

        for ($i = 0; $i < count($files); $i = $i + 2) {
            yield [$files[$i], $files[$i + 1], null];
            yield [$files[$i], $files[$i + 1], 1];
        }
    }

    /**
     * @dataProvider provideFileData
     *
     * @param string   $fileOne
     * @param string   $fileTwo
     * @param int|null $maxMemory
     */
    public function testSimultaneousReadAndWrite(string $fileOne, string $fileTwo, int $maxMemory = null): void
    {
        $bufferOne = new OutputBuffer($maxMemory);
        $bufferTwo = new OutputBuffer($maxMemory);

        $this->assertOutputBufferHasOpenResource($bufferOne);
        $this->assertOutputBufferHasOpenResource($bufferTwo);

        $this->assertOutputBufferReadsAndWrites($bufferOne, $contentsOne = file_get_contents($fileOne));
        $this->assertOutputBufferReadsAndWrites($bufferTwo, $contentsTwo = file_get_contents($fileTwo));

        $this->assertOutputBufferDoesNotContainString($bufferOne, $contentsTwo);
        $this->assertOutputBufferDoesNotContainString($bufferTwo, $contentsOne);

        $bufferOne->reset();

        $this->assertOutputBufferHasOpenResource($bufferOne);
        $this->assertOutputBufferHasOpenResource($bufferTwo);

        $this->assertOutputBufferContainsString($bufferOne, '');
        $this->assertOutputBufferContainsString($bufferTwo, $contentsTwo.PHP_EOL);

        $bufferTwo->reset();

        $this->assertOutputBufferHasOpenResource($bufferOne);
        $this->assertOutputBufferHasOpenResource($bufferTwo);

        $this->assertOutputBufferContainsString($bufferOne, '');
        $this->assertOutputBufferContainsString($bufferTwo, '');

        $bufferOne->close();

        $this->assertOutputBufferHasClosedResource($bufferOne);
        $this->assertOutputBufferHasOpenResource($bufferTwo);
        $this->assertOutputBufferReadsAndWrites($bufferTwo, $contentsTwo);

        $bufferTwo->close();

        $this->assertOutputBufferHasClosedResource($bufferOne);
        $this->assertOutputBufferHasClosedResource($bufferTwo);
    }

    public function testThrowsOnWriteWhenClosed(): void
    {
        $this->expectException(OutputException::class);
        $this->expectExceptionMessageRegExp('{Failed to write "foobar" data to closed buffer: re-open the buffer resource using the "[^"]+OutputBuffer::reset\(\)" method\.}');

        $buffer = new OutputBuffer();
        $buffer->close();
        $buffer->add('foobar');
    }

    public function testThrowsOnReadWhenClosed(): void
    {
        $this->expectException(OutputException::class);
        $this->expectExceptionMessageRegExp('{Failed to read "all" data from closed buffer: re-open the buffer resource using the "[^"]+OutputBuffer::reset\(\)" method\.}');

        $buffer = new OutputBuffer();
        $buffer->add('foobar');
        $buffer->close();
        $buffer->get();
    }

    /**
     * @param OutputBuffer $buffer
     * @param string       $contents
     */
    private function assertOutputBufferReadsAndWrites(OutputBuffer $buffer, string $contents): void
    {
        $this->assertEmpty($buffer->get());
        $this->assertSame(0, $buffer->getUsedBytes());
        $lastBytesUsed = 0;
        $lastBytesDiff = $buffer->getMaximumBytes();

        foreach (explode(PHP_EOL, $contents) as $line) {
            $buffer->add($line, true);
            $this->assertContains($line.PHP_EOL, (string) $buffer);
            $this->assertContains($line.PHP_EOL, $buffer->get());
            $this->assertGreaterThan($lastBytesUsed, $lastBytesUsed = $buffer->getUsedBytes());

            if (null === $buffer->getMaximumBytes()) {
                $this->assertNull($buffer->getAvailableBytes());
            } else {
                $this->assertLessThan($lastBytesDiff, $lastBytesDiff = $buffer->getAvailableBytes());
            }
        }

        $this->assertOutputBufferContainsString($buffer, $contents.PHP_EOL);
    }

    /**
     * @param OutputBuffer $buffer
     * @param string       $contents
     */
    private function assertOutputBufferDoesNotContainString(OutputBuffer $buffer, string $contents = ''): void
    {
        $this->assertNotSame($contents, $buffer->get());
        $this->assertNotSame($contents, (string) $buffer);
    }

    /**
     * @param OutputBuffer $buffer
     * @param string       $contents
     */
    private function assertOutputBufferContainsString(OutputBuffer $buffer, string $contents = ''): void
    {
        $this->assertSame($contents, $buffer->get());
        $this->assertSame($contents, (string) $buffer);
    }

    /**
     * @param OutputBuffer $buffer
     */
    private function assertOutputBufferHasOpenResource(OutputBuffer $buffer): void
    {
        $this->assertTrue($buffer->isResourceOpen());
        $this->assertInternalType('resource', $buffer->getResource());
    }

    /**
     * @param OutputBuffer $buffer
     */
    private function assertOutputBufferHasClosedResource(OutputBuffer $buffer): void
    {
        $this->assertFalse($buffer->isResourceOpen());
        $this->assertNull($buffer->getResource());
    }
}
