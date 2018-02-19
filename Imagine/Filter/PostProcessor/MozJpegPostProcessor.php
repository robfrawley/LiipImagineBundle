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
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Utility\Process\DescribeProcess;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @see http://calendar.perfplanet.com/2014/mozjpeg-3-0/
 * @see https://mozjpeg.codelove.de/binaries.html
 *
 * @author Rob Frawley 2nd <rmf@src.run>
 * @author Alex Wilson <a@ax.gy>
 */
class MozJpegPostProcessor extends AbstractPostProcessor
{
    /*
     * @var int|null Quality factor
     */
    protected $quality;

    /**
     * MozJpegPostProcessor constructor.
     *
     * @param string   $executablePath
     * @param int|null $quality
     */
    public function __construct(string $executablePath = '/opt/mozjpeg/bin/cjpeg', int $quality = null)
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
        if (!$this->isBinaryTypeJpgImage($binary)) {
            return $binary;
        }

        ($process = $this->createProcess($options, function (...$arguments): void {
            $this->configureProcess(...$arguments);
        }))->setInput($binary->getContent())->run();

        if (!$this->isProcessSuccess($process)) {
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
        if ($quantTable = $options['quant_table'] ?? 2) {
            $definition
                ->pushArgument('-quant-table')
                ->pushArgument($quantTable);
        }

        if ($options['optimise'] ?? true) {
            $definition->pushArgument('-optimise');
        }

        if (null !== $quality = $options['quality'] ?? $this->quality) {
            $definition
                ->pushArgument('-quality')
                ->pushArgument($quality);
        }
    }
}
