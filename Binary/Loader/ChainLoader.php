<?php

namespace Liip\ImagineBundle\Binary\Loader;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotChainLoadableException;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\InvalidArgumentException;

class ChainLoader implements ChainLoaderInterface
{
    /**
     * @var LoaderInterface[]
     */
    private $loaders = array();

    /**
     * @var \Exception[]
     */
    private $errorStack = array();

    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = array())
    {
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * @param LoaderInterface $loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        if (!$loader instanceof ChainableLoaderInterface) {
            throw new InvalidArgumentException(
                sprintf('Invalid chain loader added "%s" as it must implement ChainLoadableInterface!', get_class($loader))
            );
        }

        if (!in_array($loader, $this->loaders)) {
            $this->loaders[] = $loader;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find($path)
    {
        foreach ($this->getSupportedLoaders($path) as $loader) {
            if (null !== $return = $this->invokeLoaderFindMethod($loader, $path)) {
                return $return;
            }
        }

        throw new NotChainLoadableException(sprintf('Source image not found "%s".', $path), $this->errorStack);
    }

    /**
     * @param string $path
     *
     * @return LoaderInterface[]
     */
    private function getSupportedLoaders($path)
    {
        return array_filter($this->loaders, function (ChainableLoaderInterface $loader) use ($path) {
            return $loader->isPathSupported($path);
        });
    }

    /**
     * @param LoaderInterface $loader
     * @param mixed           $path
     *
     * @return BinaryInterface|null|string
     */
    private function invokeLoaderFindMethod(LoaderInterface $loader, $path)
    {
        try {
            return $loader->find($path);
        } catch (NotLoadableException $e) {
            $this->errorStack[] = $e;
        }

        return null;
    }
}
