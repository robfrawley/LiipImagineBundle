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
use Liip\ImagineBundle\Exception\Imagine\Filter\FilterConfigurationException;
use Liip\ImagineBundle\Exception\Imagine\Filter\FilterLoaderException;
use Liip\ImagineBundle\Utility\Filesystem\TemporaryFile;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFilterLoader implements LoaderInterface
{
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

        try {
            return $this->doLoad($image, $options);
        } catch (\Exception $exception) {
            throw new FilterLoaderException(sprintf(
                'Failed to run "%s" filter loader: %s', $this->getName(), $exception->getMessage()
            ));
        }
    }

    /**
     * @param array $options
     *
     * @return TemporaryFile
     */
    protected function getTemporaryFile(array $options = []): TemporaryFile
    {
        $temporary = new TemporaryFile($options['temp_path'], $options['temp_name'], null, null, !$options['temp_keep']);
        $temporary->acquire();

        return $temporary;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @return OptionsResolver
     */
    protected function setupOptionsResolverWithTempFileOptions(OptionsResolver $resolver): OptionsResolver
    {
        return $resolver
            ->setDefault('temp_path', sys_get_temp_dir())
            ->setDefault('temp_name', 'resample-filter')
            ->setDefault('temp_keep', false)
            ->setAllowedTypes('temp_path', array('string'))
            ->setAllowedTypes('temp_name', array('string', 'null'))
            ->setAllowedTypes('temp_keep', array('bool'));
    }

    /**
     * @return string
     */
    protected function getName(): string
    {
        return strtolower(
            trim(
                preg_replace(
                    '{(?<=\\w)(?=[A-Z])}',
                    ' $1',
                    preg_replace('{.+\\\(.+)FilterLoader}i', '$1', get_called_class())
                )
            )
        );
    }

    /**
     * @param string $format
     * @param mixed  ...$replacements
     */
    protected function triggerDeprecation(string $format, ...$replacements): void
    {
        @trigger_error(sprintf($format, ...$replacements), E_USER_DEPRECATED);
    }

    abstract protected function doLoad(ImageInterface $image, array $options = []): ImageInterface;

    /**
     * @return OptionsResolver
     */
    abstract protected function setupOptionsResolver(): OptionsResolver;

    /**
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options): array
    {
        try {
            return $this
                ->setupOptionsResolver()
                ->resolve($options);
        } catch (ExceptionInterface $e) {
            throw new FilterConfigurationException(
                sprintf('Invalid option(s) passed to %s::load(): %s', get_called_class(), $e->getMessage()), null, $e
            );
        }
    }
}
