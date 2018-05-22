<?php

/*
 * This file is part of the `src-run/augustus-utility-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Utility\Filesystem;

use Liip\ImagineBundle\Exception\Filesystem\FilesystemException;
use Liip\ImagineBundle\Utility\Filesystem\TemporaryFile;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Finder\Finder;

/**
 * @covers \Liip\ImagineBundle\Utility\Filesystem\TemporaryFile
 */
class TemporaryFileTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;

    /**
     * @var vfsStreamDirectory
     */
    private $vfsWork;

    /**
     * Setup virtual filesystem environment.
     */
    public function setUp(): void
    {
        if (!class_exists(vfsStream::class)) {
            $this->markTestSkipped(sprintf('Requires "%s"', vfsStream::class));
        }

        try {
            $this->vfsRoot = vfsStream::setup('imagine-unit-tests', 0700);
            $this->vfsRoot->chown(getmyuid());
            $this->vfsRoot->chgrp(getmygid());
            $this->vfsWork = new vfsStreamDirectory('temporary-file-test', 0777);
            $this->vfsWork->at($this->vfsRoot);
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }

        parent::setUp();
    }

    /**
     * @return \Generator
     */
    public static function provideConstructorArgumentsData(): \Generator
    {
        foreach (self::getRandomFiles(4) as $i => $file) {
            yield [
                null,
                null,
                null,
                null,
            ];
            yield [
                null,
                null,
                null,
                $blob = file_get_contents($file),
            ];
            yield [
                'foo/bar/baz',
                null,
                null,
                $blob,
            ];
            yield [
                null,
                sprintf('my-file-name-prefix-%03d', $i),
                null,
                $blob,
            ];
            yield [
                null,
                null,
                sprintf('my-ext%d', $i),
                $blob,
            ];
            yield [
                'foo/bar/baz',
                sprintf('my-file-name-prefix-%03d', $i),
                sprintf('my-ext%d', $i),
                $blob,
            ];
            yield [
                sprintf('%s/foo/bar/baz', sys_get_temp_dir()),
                sprintf('my-file-name-prefix-%03d', $i),
                sprintf('my-ext%d', $i),
                $blob,
            ];
        }
    }

    /**
     * @dataProvider provideConstructorArgumentsData
     *
     * @param string|null $root
     * @param string|null $name
     * @param string|null $type
     * @param string|null $blob
     */
    public function testConstructionAndUsage(string $root = null, string $name = null, string $type = null, string $blob = null): void
    {
        $this->assertTempFileAcquisitionAndUsage(
            $file = new TemporaryFile($root, $name, $type, $blob),
            $root,
            $name,
            $type,
            $blob
        );

        $this->assertFileExists($f = $file->stringifyFile());
        unset($file);
        $this->assertFileNotExists($f);
    }

    /**
     * @return \Generator
     */
    public static function provideDirtyConstructorArgumentsData(): \Generator
    {
        foreach (self::getRandomFiles(4) as $i => $file) {
            yield [
                'foo\\\\bar\\\\\\baz\\\\\\\\',
                'foo/bar/baz',
                sprintf('file--!-@-#-$-%%-^-&-*-(-)-%03d', $i),
                sprintf('file-%03d', $i),
                sprintf('ext--!-@-#-$-%%-^-&-*-(-)-%03d', $i),
                sprintf('ext-%03d', $i),
                file_get_contents($file),
            ];
        }
    }

    /**
     * @dataProvider provideDirtyConstructorArgumentsData
     *
     * @param string|null $root
     * @param string|null $cleanRoot
     * @param string|null $name
     * @param string|null $cleanName
     * @param string|null $type
     * @param string|null $cleanType
     * @param string|null $blob
     */
    public function testDirtyConstructionAndUsage(string $root = null, string $cleanRoot = null, string $name = null, string $cleanName = null, string $type = null, string $cleanType = null, string $blob = null): void
    {
        $this->assertTempFileAcquisitionAndUsage(
            $file = new TemporaryFile($root, $name, $type, $blob),
            $cleanRoot,
            $cleanName,
            $cleanType,
            $blob
        );

        $this->assertFileExists($f = $file->stringifyFile());
        unset($file);
        $this->assertFileNotExists($f);
    }

    /**
     * @dataProvider provideConstructorArgumentsData
     *
     * @param string|null $root
     * @param string|null $name
     * @param string|null $type
     * @param string|null $blob
     */
    public function testDisabledAutoRelease(string $root = null, string $name = null, string $type = null, string $blob = null): void
    {
        try {
            $fake = new vfsStreamDirectory('test-disabled-auto-release');
            $fake->at($this->vfsWork);

            $file = new TemporaryFile($fake->url(), $name, $type, $blob, false);
            $this->assertTempFileAcquisitionAndUsage($file, $fake->url(), $name, $type, $blob);

            $this->assertFileExists($f = $file->stringifyFile());

            unset($file);
            $this->assertFileExists($f);

            if (false === @unlink($f)) {
                $this->markTestIncomplete(sprintf('Failed to remove test file: %s', $f));
            }
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }
    }

    /**
     * @dataProvider provideConstructorArgumentsData
     *
     * @param string|null $root
     * @param string|null $name
     * @param string|null $type
     * @param string|null $blob
     */
    public function testManualRelease(string $root = null, string $name = null, string $type = null, string $blob = null): void
    {
        try {
            $fake = new vfsStreamDirectory('test-manual-release');
            $fake->at($this->vfsWork);

            $file = new TemporaryFile($fake->url(), $name, $type, $blob, false);
            $this->assertTempFileAcquisitionAndUsage($file, $fake->url(), $name, $type, $blob);

            $this->assertFileExists($f = $file->stringifyFile());
            $copy = clone $file;

            $this->assertFileExists($f);
            $copy->release(false);

            $this->assertFileExists($f);
            $file->release();

            $this->assertFileNotExists($f);
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }
    }

    /**
     * @group exceptions
     */
    public function testThrowsExceptionOnWriteContentsFailure(): void
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessageRegExp(
            '{Failed write operation: "vfs://[^"]+.temporary" \(file_put_contents\(vfs://[^)]+.temporary\): failed '.
            'to open stream: "org\\\bovigo\\\vfs\\\vfsStreamWrapper::stream_open" call failed\)}'
        );

        try {
            $path = new vfsStreamDirectory('test-throws-exception-on-write-contents-failure');
            $path->at($this->vfsWork);

            $temp = new TemporaryFile($path->url());
            $temp->acquire();

            $file = new vfsStreamFile($temp->getFile()->getBasename(), 0660);
            $file->chown(self::getRandomUserIdentity());
            $file->chgrp(self::getRandomGroupIdentity());
            $file->at($path);

            $temp->setContents('foobar');
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }
    }

    /**
     * @group exceptions
     */
    public function testThrowsExceptionOnReadContentsFailure(): void
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessageRegExp(
            '{Failed read operation: "vfs://[^"]+.temporary" \(file_get_contents\(vfs://[^)]+.temporary\): failed to '.
            'open stream: "org\\\bovigo\\\vfs\\\vfsStreamWrapper::stream_open" call failed\)}'
        );

        $temp = new TemporaryFile($this->vfsWork->url());
        $temp->acquire();

        $file = new vfsStreamFile($temp->getFile()->getBasename(), 0660);
        $file->chown(self::getRandomUserIdentity());
        $file->chgrp(self::getRandomGroupIdentity());
        $file->at($this->vfsWork);

        $temp->getContents();
    }

    /**
     * @group exceptions
     */
    public function testThrowsExceptionOnDeleteFileFailure(): void
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessageRegExp('{Failed remove operation: "vfs://[^"]+.temporary"}');

        try {
            $path = new vfsStreamDirectory('test-throws-exception-on-delete-file-failure', 0600);
            $path->at($this->vfsWork);

            $temp = new TemporaryFile($path->url());
            $temp->acquire();

            $file = new vfsStreamFile($temp->getFile()->getBasename(), 0600);
            $file->at($path);

            $temp->setContents('foobar');

            $file->chown(self::getRandomUserIdentity());
            $file->chgrp(self::getRandomGroupIdentity());
            $path->chown(self::getRandomUserIdentity());
            $path->chgrp(self::getRandomGroupIdentity());

            $temp->release(true);
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }
    }

    /**
     * @group exceptions
     */
    public function testThrowsExceptionOnRootNotCreatable(): void
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessageRegExp('{Failed create operation: "vfs://[^"]+/not-writable/not-creatable"}');

        try {
            $file = new vfsStreamDirectory('not-writable', 0000);
            $file->at($this->vfsWork);

            $temp = new TemporaryFile(sprintf('%s/not-creatable', $file->url()));
            $temp->acquire();
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }
    }

    /**
     * @group exceptions
     */
    public function testThrowsExceptionOnRootNotWritable(): void
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessageRegExp('{Failed writable check: "vfs://[^"]+/not-writable"}');

        try {
            $file = new vfsStreamDirectory('not-writable', 0000);
            $file->at($this->vfsWork);

            $temp = new TemporaryFile($file->url());
            $temp->acquire();
        } catch (vfsStreamException $e) {
            $this->fail(sprintf('Failed setup virtual filesystem: %s', $e->getMessage()));
        }
    }

    /**
     * @param int|null    $limit
     * @param bool        $even
     * @param string|null $root
     * @param string|null $name
     *
     * @return string[]
     */
    public static function getRandomFiles(int $limit = null, bool $even = false, string $root = null, string $name = null):
    array {
        $files = array_map(function (\SplFileInfo $file): string {
            return realpath($file->getPathname());
        }, iterator_to_array(
            (new Finder())
                ->in($root ?? sprintf('%s/../../../', __DIR__))
                ->depth(3)
                ->name($name ?? '*.php')
                ->files()
        ));

        shuffle($files);

        $count = count($files);
        $limit = $limit ?: $count;

        if ($even && 0 !== $limit % 2) {
            $limit = $limit + (1 === $limit ? 1 : -1);
        }

        return array_slice($files, 0, $limit > $count ? $count : $limit, false);
    }

    /**
     * @param TemporaryFile $temp
     * @param string|null   $root
     * @param string|null   $name
     * @param string|null   $type
     * @param string|null   $blob
     */
    private function assertTempFileAcquisitionAndUsage(TemporaryFile $temp, string $root = null, string $name = null, string $type = null, string $blob = null): void
    {
        if (null === $blob) {
            $this->assertFalse($temp->isAcquired());
            $this->assertFalse($temp->exists());
            $this->assertNull($temp->getFile());
            $this->assertNull($temp->stringifyFile());
            $this->assertSame(0, $temp->getBytes());
        } else {
            $this->assertTrue($temp->isAcquired());
            $this->assertTrue($temp->exists());
            $this->assertInstanceOf(\SplFileInfo::class, $temp->getFile());
            $this->assertNotNull($temp->stringifyFile());
            $this->assertSame($this->calculateBlobFileSizeBytes($blob), $temp->getBytes());
        }

        $temp->acquire();

        $this->assertTrue($temp->isAcquired());
        $this->assertInstanceOf(UuidInterface::class, $temp->getUuid());
        $this->assertSame(self::normalizeRoot($root), $temp->getRoot());
        $this->assertSame($type ?? 'temporary', $temp->getType());

        $this->assertStringMatchesFormat(
            vsprintf('%s/imagine-bundle-%s-%%s-%%s-%%s-%%s-%%s.%s', [
                self::normalizeRoot($root),
                $name ?? '%s',
                $type ?? 'temporary',
            ]),
            $temp->stringifyFile()
        );

        $this->assertSame($blob, $temp->getContents());
        $this->assertSame($this->calculateBlobFileSizeBytes($blob), $temp->getBytes());

        $temp->setContents($blob);
        $this->assertSame($blob, $temp->getContents());
        $this->assertSame($this->calculateBlobFileSizeBytes($blob), $temp->getBytes());

        $temp->setContents();
        $this->assertNull($temp->getContents());
        $this->assertSame(0, $temp->getBytes());

        if (null !== $blob) {
            for ($i = 0; $i < 10; $i++) {
                $temp->addContents($blob);
                $this->assertSame($expected = str_repeat($blob, $i + 1), $temp->getContents());
                $this->assertSame($this->calculateBlobFileSizeBytes($expected), $temp->getBytes());
            }
        }
    }

    /**
     * @param string|null $blob
     *
     * @return int
     */
    private function calculateBlobFileSizeBytes(string $blob = null): int
    {
        if (null === $blob) {
            return 0;
        }

        if (false === @file_put_contents($file = tempnam(sys_get_temp_dir(), 'imagine-bundle-temporary-file-test-size'), $blob)) {
            $this->markTestSkipped(sprintf('Failed to create temporary file to determine size of blob: %s', $blob));
        }

        try {
            return @filesize($file) ?: 0;
        } finally {
            @unlink($file);
        }
    }

    /**
     * @return int
     */
    private static function getRandomUserIdentity(): int
    {
        return vfsStream::getCurrentUser() + mt_rand(1, 100);
    }

    /**
     * @return int
     */
    private static function getRandomGroupIdentity(): int
    {
        return vfsStream::getCurrentGroup() + mt_rand(1, 100);
    }

    /**
     * @param string|null $root
     *
     * @return string
     */
    private static function normalizeRoot(string $root = null): string
    {
        if (null === $root) {
            return sys_get_temp_dir();
        }

        if (in_array(substr($root, 0, 1), ['\\', '/']) || null !== parse_url($root, PHP_URL_SCHEME)) {
            return $root;
        }

        return sprintf('%s/%s', sys_get_temp_dir(), $root);
    }
}
