<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Imagine\Filter\PostProcessor;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Exception\Imagine\Filter\PostProcessor\InvalidOptionException;
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Utility\Process\DescribeProcess;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
final class JpegOptimPostProcessor extends AbstractPostProcessor
{
    /**
     * If set --strip-all will be passed to jpegoptim.
     *
     * @var bool
     */
    protected $strip;

    /**
     * If set, --max=$value will be passed to jpegoptim.
     *
     * @var int
     */
    protected $quality;

    /**
     * If set to true --all-progressive will be passed to jpegoptim, otherwise --all-normal will be passed.
     *
     * @var bool
     */
    protected $progressive;

    /**
     * JpegOptimPostProcessor constructor.
     *
     * @param string      $executablePath
     * @param bool        $strip
     * @param bool|null   $quality
     * @param bool        $progressive
     * @param string|null $temporaryBasePath
     */
    public function __construct(string $executablePath = '/usr/bin/jpegoptim', bool $strip = true, bool $quality = null, bool $progressive = true, string $temporaryBasePath = null)
    {
        parent::__construct($executablePath, $temporaryBasePath);

        $this->strip = $strip;
        $this->quality = $quality;
        $this->progressive = $progressive;
    }

    /**
     * @param BinaryInterface $binary
     * @param array           $options
     *
     * @throws ProcessFailedException
     *
     * @return BinaryInterface
     */
    public function process(BinaryInterface $binary, array $options = []): BinaryInterface
    {
        if (!$this->isBinaryTypeJpgImage($binary)) {
            return $binary;
        }

        $file = $this->writeTemporaryFile($binary, $options, 'imagine-post-processor-optipng');

        ($process = $this->createProcess($options, function (...$arguments): void {
            $this->configureProcess(...$arguments);
        }, $file))->run();

        if (!$this->isProcessSuccess($process)) {
            unlink($file);
            throw new ProcessFailedException($process);
        }

        $result = new Binary(file_get_contents($file), $binary->getMimeType(), $binary->getFormat());
        unlink($file);

        return $result;
    }

    /**
     * @param DescribeProcess $definition
     * @param array           $options
     * @param string          $temporaryFile
     */
    private function configureProcess(DescribeProcess $definition, array $options, string $temporaryFile): void
    {
        if ($options['strip_all'] ?? $this->strip) {
            $definition->pushArgument('--strip-all');
        }

        if ($quality = $options['quality'] ?? $this->quality) {
            if (100 < $quality || 0 > $quality) {
                throw new InvalidOptionException('the "quality" option must be an int between 0 and 100', $options);
            }

            $definition->pushArgument('--max=%d', $quality);
        }

        $definition->pushArgument($options['progressive'] ?? $this->progressive ? '--all-progressive' : '--all-normal');
        $definition->pushArgument($temporaryFile);
    }
}
