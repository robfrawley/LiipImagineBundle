<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Templating;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Utility\Context\UriContext;

class ImagineExtension extends \Twig_Extension
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var bool
     */
    private $uriQueryOriginRemove = true;

    /**
     * @var bool
     */
    private $uriQueryOutputAppend = true;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param bool $uriQueryOriginRemove
     * @param bool $uriQueryOutputAppend
     */
    public function setUriQueryBehavior($uriQueryOriginRemove, $uriQueryOutputAppend)
    {
        $this->uriQueryOriginRemove = $uriQueryOriginRemove;
        $this->uriQueryOutputAppend = $uriQueryOutputAppend;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('imagine_filter', array($this, 'filter')),
        );
    }

    /**
     * Gets the browser path for the image and filter to apply.
     *
     * @param string $path
     * @param string $filter
     * @param array  $runtimeConfig
     * @param string $resolver
     *
     * @return string
     */
    public function filter($path, $filter, array $runtimeConfig = array(), $resolver = null)
    {
        $origin = new UriContext($path);
        $output = new UriContext($this->cacheManager->getBrowserPath(
            $origin->getUri(!$this->uriQueryOriginRemove), $filter, $runtimeConfig, $resolver
        ));

        return $this->uriQueryOutputAppend ? $output->addQuery($origin->getQuery())->getUri() : $output->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'liip_imagine';
    }
}
