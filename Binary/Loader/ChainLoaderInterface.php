<?php

namespace Liip\ImagineBundle\Binary\Loader;

interface ChainLoaderInterface extends LoaderInterface
{
    /**
     * @param LoaderInterface $loader
     */
    public function addLoader(LoaderInterface $loader);
}
