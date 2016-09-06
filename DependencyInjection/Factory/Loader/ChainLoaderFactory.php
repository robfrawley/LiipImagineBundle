<?php

namespace Liip\ImagineBundle\DependencyInjection\Factory\Loader;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class ChainLoaderFactory implements LoaderFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $loaderName, array $config)
    {
        $loaderDefinition = new DefinitionDecorator('liip_imagine.binary.loader.prototype.chain');
        $loaderDefinition->replaceArgument(2, $config['loaders']);
        $loaderDefinition->addTag('liip_imagine.binary.loader', array(
            'loader' => $loaderName,
        ));
        $loaderId = 'liip_imagine.binary.loader.'.$loaderName;

        $container->setDefinition($loaderId, $loaderDefinition);

        return $loaderId;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chain';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('cache_path')->defaultValue('%kernel.cache_dir%/liip/')->cannotBeEmpty()->end()
            ->end()
        ;
    }
}
