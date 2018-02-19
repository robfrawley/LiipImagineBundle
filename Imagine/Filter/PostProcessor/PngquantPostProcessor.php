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
 * @see https://pngquant.org/
 *
 * @author Rob Frawley 2nd <rmf@src.run>
 * @author Alex Wilson <a@ax.gy>
 */
class PngquantPostProcessor extends AbstractPostProcessor
{
    /**
     * @var string Quality to pass to pngquant
     */
    protected $quality;

    /**
     * @param string $executablePath
     * @param int[]  $quality
     */
    public function __construct(string $executablePath = '/usr/bin/pngquant', array $quality = [80, 100])
    {
        parent::__construct($executablePath);

        $this->quality = $quality;
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
        if (!$this->isBinaryTypePngImage($binary)) {
            return $binary;
        }

        ($process = $this->createProcess($options, function (...$arguments): void {
            $this->configureProcess(...$arguments);
        }))->setInput($binary->getContent())->run();

        if (!$this->isProcessSuccess($process, [0, 98, 99], [])) {
            throw new ProcessFailedException($process);
        }

        return new Binary($process->getOutput(), $binary->getMimeType(), $binary->getFormat());
    }

    /**
     * @param DescribeProcess $definition
     * @param array           $options
     */
    private function configureProcess(DescribeProcess $definition, array $options): void
    {
        if ($quality = $options['quality'] ?? $this->quality) {
            if (!is_array($quality)) {
                $quality = [(int)$quality];
            }

            if (1 === count($quality)) {
                array_unshift($quality, 0);
            }

            if ($quality[0] > $quality[1]) {
                throw new InvalidOptionException('the "quality" option cannot have a greater minimum value value than maximum quality value', $options);
            }

            if ((100 < $quality[0] || 0 > $quality[0]) || (100 < $quality[1] || 0 > $quality[1])) {
                throw new InvalidOptionException('the "quality" option value(s) must be an int between 0 and 100', $options);
            }

            $definition
                ->pushArgument('--quality')
                ->pushArgument('%d-%d', ...$quality);
        }

        $definition->pushArgument('-');
    }
}
