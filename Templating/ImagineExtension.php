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
        $output = new UriContext($this->cacheManager->getBrowserPath($origin->getUri(!$this->removeUriQuery), $filter, $runtimeConfig, $resolver));

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
