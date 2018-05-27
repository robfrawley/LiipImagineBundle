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

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Exception\Imagine\Filter\FilterLoaderException;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResampleFilterLoader extends AbstractFilterLoader
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @param ImagineInterface $imagine
     */
    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    /**
     * @param ImageInterface $image
     * @param array          $options
     *
     * @throws FilterLoaderException
     *
     * @return ImageInterface
     */
    public function doLoad(ImageInterface $image, array $options = []): ImageInterface
    {
        $temporary = $this->getTemporaryFile($options);

        $image->save($temporary->stringifyFile(), [
            'resolution-units' => $options['unit'],
            'resolution-x' => $options['x'],
            'resolution-y' => $options['y'],
            'resampling-filter' => $options['filter'],
        ]);

        return $this->imagine->open($temporary->stringifyFile());
    }

    /**
     * @return OptionsResolver
     */
    protected function setupOptionsResolver(): OptionsResolver
    {
        return $this->setupOptionsResolverWithTempFileOptions(
            (new OptionsResolver())
                ->setRequired(array('x', 'y', 'unit', 'temp_path'))
                ->setAllowedTypes('x', array('int', 'float'))
                ->setAllowedTypes('y', array('int', 'float'))
                ->setAllowedValues('unit', array(
                    ImageInterface::RESOLUTION_PIXELSPERINCH,
                    ImageInterface::RESOLUTION_PIXELSPERCENTIMETER
                ))
                ->setDefault('filter', ImageInterface::FILTER_UNDEFINED)
                ->setAllowedTypes('filter', array('string'))
                ->setNormalizer('filter', function (Options $options, $value) {
                    return $this->normalizeFilterOption($value);
                })
        );
    }

    /**
     * @param Options $options
     * @param string  $value
     *
     * @return string
     */
    private function normalizeFilterOption(string $value): string
    {
        foreach (array(sprintf('%s::FILTER_%%s', ImageInterface::class), sprintf('%s::%%s', ImageInterface::class), '%s') as $format) {
            if (defined($constant = sprintf($format, strtoupper($value))) || defined($constant = sprintf($format, $value))) {
                return constant($constant);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid value for "filter" option: must be a valid constant resolvable using one of formats "%s::FILTER_%%s", "%1$s::%%s", or "%%s".',
            ImageInterface::class
        ));
    }
}
