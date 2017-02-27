<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\DependencyInjection\Factory\Loader;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FileSystemLoaderFactory extends AbstractLoaderFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $loaderName, array $config)
    {
        $definition = $this->getChildLoaderDefinition();
        $definition->replaceArgument(2, $this->getDataRoots($config['data_root'], $config['bundle_resources'], $container));
        $definition->replaceArgument(3, new Reference(sprintf('liip_imagine.binary.locator.%s', $config['locator'])));

        return $this->setTaggedLoaderDefinition($loaderName, $definition, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filesystem';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->arrayNode('bundle_resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_register')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('black_list')
                            ->defaultValue(array())
                            ->prototype('scalar')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->enumNode('locator')
                    ->values(array('filesystem', 'filesystem_insecure'))
                    ->info('Using the "filesystem_insecure" locator is not recommended due to a less secure resolver mechanism, but is provided for those using heavily symlinked projects.')
                    ->defaultValue('filesystem')
                ->end()
                ->arrayNode('data_root')
                    ->beforeNormalization()
                    ->ifString()
                        ->then(function ($value) { return array($value); })
                    ->end()
                    ->defaultValue(array('%kernel.root_dir%/../web'))
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param string[]         $userRoots
     * @param array            $bundleConfig
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getDataRoots(array $userRoots, array $bundleConfig, ContainerBuilder $container)
    {
        if (false === $bundleConfig['auto_register']) {
            return $userRoots;
        }

        $resourceRoots = array_filter($this->getBundleResourcePaths($container), function ($path, $name) use ($bundleConfig) {
            return is_dir($path) && false === in_array($name, $bundleConfig['black_list']);
        }, ARRAY_FILTER_USE_BOTH);

        return array_merge($userRoots, $resourceRoots);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getBundleResourcePaths(ContainerBuilder $container)
    {
        $paths = array();

        if ($container->hasParameter('kernel.bundles_metadata')) {
            foreach ($container->getParameter('kernel.bundles_metadata') as $name => $metadata) {
                $paths[$name] = $metadata['path'];
            }
        } else {
            foreach ($container->getParameter('kernel.bundles') as $name) {
                $reflectClass = new \ReflectionClass($name);
                $paths[$name] = dirname($reflectClass->getFileName());
            }
        }

        return array_map(function ($path) {
            return $path.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'public';
        }, $paths);
    }
}
