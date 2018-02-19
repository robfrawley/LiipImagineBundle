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
class OptiPngPostProcessor extends AbstractPostProcessor
{
    /**
     * If set --oN will be passed to optipng.
     *
     * @var int
     */
    protected $level;

    /**
     * If set --strip=all will be passed to optipng.
     *
     * @var bool
     */
    protected $strip;

    /**
     * @param string      $executablePath
     * @param int         $level
     * @param bool        $strip
     * @param string|null $temporaryBasePath
     */
    public function __construct(string $executablePath = '/usr/bin/optipng', int $level = 7, bool $strip = true, string $temporaryBasePath = null)
    {
        parent::__construct($executablePath, $temporaryBasePath);

        $this->level = $level;
        $this->strip = $strip;
    }

    /**
     * @param BinaryInterface $binary
     * @param array           $options
     *
     * @throws ProcessFailedException
     *
     * @return BinaryInterface|Binary
     */
    public function process(BinaryInterface $binary, array $options = []): BinaryInterface
    {
        if (!$this->isBinaryTypePngImage($binary)) {
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
        if (null !== $level = $options['level'] ?? $this->level) {
            if (7 < $level || 0 > $level) {
                throw new InvalidOptionException('the "level" option must be an int between 0 and 7', $options);
            }

            $definition->pushArgument('-o%d', $level);
        }

        if ($strip = $options['strip'] ?? $this->strip) {
            $definition
                ->pushArgument('-strip')
                ->pushArgument($strip ? 'all' : $strip);
        }

        if (true === $options['snip'] ?? false) {
            $definition->pushArgument('-snip');
        }

        if (true === $options['preserve_attributes'] ?? false) {
            $definition->pushArgument('-preserve');
        }

        if ($interlaceType = $options['interlace_type'] ?? false) {
            if (1 < $interlaceType || 0 > $interlaceType) {
                throw new InvalidOptionException('the "interlace_type" option must be either 0 or 1', $options);
            }

            $definition
                ->pushArgument('-i')
                ->pushArgument($interlaceType);
        }

        if (true === $options['no_bit_depth_reductions'] ?? false) {
            $definition->pushArgument('-nb');
        }

        if (true === $options['no_color_type_reductions'] ?? false) {
            $definition->pushArgument('-nc');
        }

        if (true === $options['no_palette_reductions'] ?? false) {
            $definition->pushArgument('-np');
        }

        if (true === $options['no_reductions']) {
            $definition->pushArgument('-nx');
        }

        $definition->pushArgument($temporaryFile);
    }
}
