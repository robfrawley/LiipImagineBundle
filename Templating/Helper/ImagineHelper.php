<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Templating\Helper;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Utility\Context\UriContext;
use Symfony\Component\Templating\Helper\Helper;

class ImagineHelper extends Helper
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var bool
     */
    private $removeUriQuery = true;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param bool $removeUriQuery
     */
    public function setRemoveUriQuery($removeUriQuery)
    {
        $this->removeUriQuery = $removeUriQuery;
    }

    /**
     * Gets the browser path for the image and filter to apply.
     *
     * @param string $path
     * @param string $filter
     * @param array  $runtimeConfig
     *
     * @return string
     */
    public function filter($path, $filter, array $runtimeConfig = array())
    {
        $origin = new UriContext($path);
        $output = new UriContext($this->cacheManager->getBrowserPath($origin->getUri(!$this->removeUriQuery), $filter, $runtimeConfig));

        return $this->removeUriQuery ? $output->addQuery($origin->getQuery())->getUri() : $output->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'liip_imagine';
    }
}
