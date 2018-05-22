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
use Liip\ImagineBundle\Exception\Imagine\Filter\FilterConfigurationException;
use Liip\ImagineBundle\Exception\Imagine\Filter\FilterLoaderException;
use Liip\ImagineBundle\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Utility\Filesystem\TemporaryFile;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResampleFilterLoader implements LoaderInterface
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
    public function load(ImageInterface $image, array $options = array())
    {
        $options = $this->resolveOptions($options);
        $tmpFile = new TemporaryFile($options['temp_name'], $options['temp_path']);
        $tmpFile->acquire();
        var_dump([
            $image,
            $tmpFile,
            $tmpFile->stringifyFile(),
            $options,
        ]);
die();
        try {
            $image->save($tmpFile->stringifyFile(), [
                'resolution-units' => $options['unit'],
                'resolution-x' => $options['x'],
                'resolution-y' => $options['y'],
                'resampling-filter' => $options['filter'],
            ]);

            return $this->imagine->open($tmpFile->stringifyFile());
        } catch (\Exception $exception) {
            throw new FilterLoaderException(
                sprintf('Failed to resample image using temporary file: "%s".', $tmpFile->stringifyFile()), null, $exception
            );
        }
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options): array
    {
        try {
            return $this->setupOptionsResolver()->resolve($options);
        } catch (ExceptionInterface $e) {
            throw new FilterConfigurationException(sprintf('Invalid option(s) passed in %s().', __METHOD__), null, $e);
        }
    }

    /**
     * @return OptionsResolver
     */
    private function setupOptionsResolver(): OptionsResolver
    {
        return (new OptionsResolver())

            // required options
            ->setRequired(array('x', 'y', 'unit', 'temp_path'))

            // x and y resolution (as ppi or cci)
            ->setAllowedTypes('x', array('int', 'float'))
            ->setAllowedTypes('y', array('int', 'float'))

            // resolution unit (one of ppi or cci)
            ->setAllowedValues('unit', array(
                ImageInterface::RESOLUTION_PIXELSPERINCH,
                ImageInterface::RESOLUTION_PIXELSPERCENTIMETER
            ))

            // filter to apply for resampling operation
            ->setDefault('filter', ImageInterface::FILTER_UNDEFINED)
            ->setAllowedTypes('filter', array('string'))
            ->setNormalizer('filter', function (Options $options, $value) {
                return $this->normalizeFilterOption($value);
            })

            // temporary directory to use for work (deprecated)
            ->setDefault('temp_dir', null)
            ->setAllowedTypes('temp_dir', array('string', 'null'))

            // temporary directory to use for work
            ->setDefault('temp_path', sys_get_temp_dir())
            ->setAllowedTypes('temp_path', array('string'))
            ->setNormalizer('temp_path', function (Options $options, $value) {
                return $this->normalizeTempPathOption($options, $value);
            })

            // temporary file name (prefix) to use for work
            ->setDefault('temp_name', null)
            ->setAllowedTypes('temp_name', array('string', 'null'))
        ;
    }

    /**
     * @param Options $options
     * @param string  $value
     *
     * @return string|null
     */
    private function normalizeTempPathOption(Options $options, string $value = null): ?string
    {
        if (null === $value && null !== $options['temp_dir']) {
            @trigger_error(
                'The "resample" filter loader\'s "temp_dir" configuration option was deprecated in v2.1.0 and will be '.
                'removed in 3.0.0. Please upgrade your usage to instead use the "temp_path" option.', E_USER_DEPRECATED
            );

            return $options['temp_dir'];
        }

        return $value;
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
