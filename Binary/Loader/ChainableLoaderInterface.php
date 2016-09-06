<?php

namespace Liip\ImagineBundle\Binary\Loader;

interface ChainableLoaderInterface extends LoaderInterface
{
    /**
     * @param string $path
     *
     * @return false|mixed
     */
    public function isPathSupported($path);
}
