<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Exception;

use Liip\ImagineBundle\Exception\CompilableExceptionTrait;
use Liip\ImagineBundle\Tests\AbstractTest;

/**
 * @covers \Liip\ImagineBundle\Exception\CompilableExceptionTrait
 */
class CompilableExceptionTraitTest extends AbstractTest
{
    public function testConstructionBasic()
    {
        $exception = $this->createCompilableExceptionEmptyInstance(
            $string = 'Exception contains static string for message!'
        );

        $this->assertNull($exception->getPrevious());
        $this->assertSame($string, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return \Iterator
     */
    public static function provideAdvancedConstructionData(): \Iterator
    {
        $exc = new \RuntimeException('previous-exception', 12345);
        $objCan = new class {
            public function __toString(): string {
                return 'user';
            }
        };
        $objNot = new class {};

        yield ['Just one simple %s replacements.', null, 'string'];
        yield ['Just one simple %s replacements.', $exc, 'string'];
        yield ['Cannot create "%s" in "%s" env with package v%d.', $exc, $objCan, 'dev', 10];
        yield ['Cannot create "%s" in "%s" env with package v%d.', null, $objCan, 'dev', 10];
        yield ['Complex type requiring dump: %s', null, ['foo' => 'bar', 'obj' => $objCan]];
        yield ['Complex type requiring dump: %s', $exc, ['foo' => 'bar', 'obj' => $objCan]];
        yield ['Complex obj without string cast: %s', null, $objNot];
        yield ['Complex obj without string cast: %s', $exc, $objNot];
        yield ['Complex nested array: %s', $exc, self::createNestedComplexArray(5, $objCan, $objNot)];
    }

    /**
     * @dataProvider provideAdvancedConstructionData
     *
     * @param string          $format
     * @param \Exception|null $previous
     * @param mixed           ...$replacements
     */
    public function testAdvancedConstruction(string $format, \Exception $previous = null, ...$replacements): void
    {
        $arguments = $replacements;

        if ($previous) {
            $arguments[] = $previous;
        }

        $exception = $this->createCompilableExceptionInstance($format, ...$arguments);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(321, $exception->getCode());
        $this->assertStringMatchesFormat($format, $exception->getMessage());

        $resolveReplacements = $this->getAccessiblePrivateMethod($exception, 'resolveReplacements');
        $this->assertSame(vsprintf($format, $resolveReplacements->invoke($exception, $replacements)), $exception->getMessage());
    }

    /**
     * @param string $format
     * @param mixed  ...$arguments
     *
     * @return \Exception|CompilableExceptionTrait
     */
    private function createCompilableExceptionInstance(string $format, ...$arguments): \Exception
    {
        return new class($format, ...$arguments) extends \Exception {
            use CompilableExceptionTrait;

            private const USE_EXCEPTION_CODE = 321;

            public function __construct(string $format, ...$arguments)
            {
                parent::__construct(...$this->mapToNativeConstructArguments($format, ...$arguments));
            }
        };
    }

    /**
     * @param string $message
     *
     * @return \Exception|CompilableExceptionTrait
     */
    private function createCompilableExceptionEmptyInstance(string $message): \Exception
    {
        return new class($message) extends \Exception {
            use CompilableExceptionTrait;

            public function __construct(string $format, ...$arguments)
            {
                parent::__construct(...$this->mapToNativeConstructArguments($format, ...$arguments));
            }
        };
    }

    /**
     * @param int        $level
     * @param object     $objCan
     * @param object     $objNot
     *
     * @return array
     */
    private static function createNestedComplexArray(int $level = 1, $objCan, $objNot): array
    {
        $a = [
            'class' => new \stdClass(),
            'integer' => 100,
            'float' => 28.543,
            'boolean' => true,
            'string' => 'abcdefghijklmnopqrstuvwxyz',
            'not-str-cast' => $objNot,
            'can-str-cast' => $objCan,
        ];

        if (--$level > 0) {
            $a['nested-array'] = self::createNestedComplexArray($level, $objCan, $objNot);
        }

        uasort($a, function (): int {
            return 0 === mt_rand(0, 1) ? -1 : 1;
        });

        return $a;
    }
}
