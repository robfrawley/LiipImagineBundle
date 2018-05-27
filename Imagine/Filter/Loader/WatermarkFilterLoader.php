<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Imagine\Filter\Loader;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WatermarkFilterLoader extends AbstractFilterLoader
{
    /**
     * @var ImagineInterface
     */
    protected $imagine;

    /**
     * @var string
     */
    protected $defaultRootPath;

    /**
     * @param ImagineInterface $imagine
     * @param string           $defaultRootPath
     */
    public function __construct(ImagineInterface $imagine, string $defaultRootPath)
    {
        $this->imagine = $imagine;
        $this->defaultRootPath = $defaultRootPath;
    }

    /**
     * @param ImageInterface $image
     * @param array          $options
     *
     * @return ImageInterface|static
     */
    public function doLoad(ImageInterface $image, array $options = []): ImageInterface
    {
        $mark = $this->imagine->open($options['image']);
        $size = $image->getSize();

        return $image->paste($mark, $this->getPoint(
            $options, $size, $this->sizeWatermark($mark, $size, $options['size'])
        ));
    }

    /**
     * @return OptionsResolver
     */
    protected function setupOptionsResolver(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setRequired('image')
            ->setAllowedTypes('image', 'string')
            ->setNormalizer('image', function (Options $options, $value) {
                return $this->normalizeImageFileOption($options, $value);
            })
            ->setDefault('image_path', $this->defaultRootPath)
            ->setAllowedTypes('image_path', ['string'])
            ->setDefault('size', 1.0)
            ->setAllowedTypes('size', ['string', 'int', 'float'])
            ->setNormalizer('size', function (Options $options, $value) {
                return $this->normalizeSizeOption($value);
            })
            ->setDefault('position', 'center')
            ->setAllowedValues('position', [
                'topleft',
                'top-left',
                'top',
                'topright',
                'top-right',
                'left',
                'center-left',
                'center',
                'right',
                'center-right',
                'bottomleft',
                'bottom-left',
                'bottom',
                'bottomright',
                'bottom-right',
            ])
            ->setNormalizer('position', function (Options $options, $value) {
                return $this->normalizePositionOption($value);
            });
    }

    /**
     * @param ImageInterface $wMark
     * @param BoxInterface   $imageSize
     * @param float          $wMarkUserSize
     *
     * @return BoxInterface
     */
    private function sizeWatermark(ImageInterface $wMark, BoxInterface $imageSize, float $wMarkUserSize): BoxInterface
    {
        $wMarkSize = $wMark->getSize();

        $factor = $wMarkUserSize * min($imageSize->getWidth() / $wMarkSize->getWidth(), $imageSize->getHeight() / $wMarkSize->getHeight());

        return $wMark
            ->resize(new Box($wMarkSize->getWidth() * $factor, $wMarkSize->getHeight() * $factor))
            ->getSize();
    }

    /**
     * @param array        $options
     * @param BoxInterface $imageSize
     * @param BoxInterface $wMarkSize
     *
     * @return Point
     */
    private function getPoint(array $options, BoxInterface $imageSize, BoxInterface $wMarkSize): Point
    {
        switch ($options['position']) {
            case 'top-left':
                return new Point(0, 0);

            case 'top-right':
                return new Point($imageSize->getWidth() - $wMarkSize->getWidth(), 0);

            case 'top':
                return new Point(($imageSize->getWidth() - $wMarkSize->getWidth()) / 2, 0);

            case 'bottom-left':
                return new Point(0, $imageSize->getHeight() - $wMarkSize->getHeight());

            case 'bottom-right':
                return new Point(
                    $imageSize->getWidth() - $wMarkSize->getWidth(),
                    $imageSize->getHeight() - $wMarkSize->getHeight()
                );

            case 'bottom':
                return new Point(
                    ($imageSize->getWidth() - $wMarkSize->getWidth()) / 2,
                    $imageSize->getHeight() - $wMarkSize->getHeight()
                );

            case 'center-left':
                return new Point(0, ($imageSize->getHeight() - $wMarkSize->getHeight()) / 2);

            case 'center-right':
                return new Point(
                    $imageSize->getWidth() - $wMarkSize->getWidth(),
                    ($imageSize->getHeight() - $wMarkSize->getHeight()) / 2
                );

            case 'center':
            default:
                return new Point(
                    ($imageSize->getWidth() - $wMarkSize->getWidth()) / 2,
                    ($imageSize->getHeight() - $wMarkSize->getHeight()) / 2
                );
        }
    }

    /**
     * @param Options $options
     * @param string  $image
     *
     * @return string
     */
    private function normalizeImageFileOption(Options $options, string $image): string
    {
        if (false !== $real = realpath($options['image_path'].DIRECTORY_SEPARATOR.$image)) {
            return $real;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid image "%s" for root path "%s" or "%s".', $image, $this->defaultRootPath, $options['image_path'] ?? '<null>'
        ));
    }

    /**
     * @param null|string|int|float $size
     *
     * @return null|float
     */
    private function normalizeSizeOption($size): ?float
    {
        if ('%' === mb_substr($size, -1)) {
            return mb_substr($size, 0, -1) / 100;
        }

        return $size;
    }

    /**
     * @param null|string $position
     *
     * @return string
     */
    private function normalizePositionOption($position): string
    {
        if (1 === preg_match('{(?<y>top|bottom)(?<x>left|right)}', $position, $match)) {
            $this->triggerDeprecation(
                'The "%s" option was deprecated in 2.1.0 and will be removed in 3.0. Use "%s" instead.',
                $position,
                $normalized = sprintf('%s-%s', $match['y'], $match['x'])
            );

            return $normalized;
        }

        if (in_array($position, ['left', 'right'])) {
            $this->triggerDeprecation(
                'The "%s" option was deprecated in 2.1.0 and will be removed in 3.0. Use "center-%s" instead.',
                $position,
                $normalized = sprintf('center-%s', $position)
            );

            return $normalized;
        }

        return $position;
    }
}
