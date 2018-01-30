<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Imagine\Cache;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Events\CacheResolveEvent;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\ImagineEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CacheManager
{
    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ResolverInterface[]
     */
    protected $resolvers = [];

    /**
     * @var SignerInterface
     */
    protected $signer;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var string
     */
    protected $defaultResolver;

    /**
     * Constructs the cache manager to handle Resolvers based on the provided FilterConfiguration.
     *
     * @param FilterConfiguration      $filterConfig
     * @param RouterInterface          $router
     * @param SignerInterface          $signer
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $defaultResolver
     */
    public function __construct(
        FilterConfiguration $filterConfig,
        RouterInterface $router,
        SignerInterface $signer,
        EventDispatcherInterface $dispatcher,
        $defaultResolver = null
    ) {
        $this->filterConfig = $filterConfig;
        $this->router = $router;
        $this->signer = $signer;
        $this->dispatcher = $dispatcher;
        $this->defaultResolver = $defaultResolver ?: 'default';
    }

    /**
     * Adds a resolver to handle cached images for the given filter.
     *
     * @param string            $filter
     * @param ResolverInterface $resolver
     */
    public function addResolver(string $filter, ResolverInterface $resolver): void
    {
        $this->resolvers[$filter] = $resolver;

        if ($resolver instanceof CacheManagerAwareInterface) {
            $resolver->setCacheManager($this);
        }
    }

    /**
     * Gets a resolver for the given filter.
     *
     * In case there is no specific resolver, but a default resolver has been configured, the default will be returned.
     *
     * @param string $filter
     * @param string $resolver
     *
     * @throws \OutOfBoundsException If neither a specific nor a default resolver is available
     *
     * @return ResolverInterface
     */
    protected function getResolver(string $filter, string $resolver = null): ResolverInterface
    {
        $resolver = $resolver ?? ($this->filterConfig->get($filter)['cache'] ?? $this->defaultResolver);

        if (!isset($this->resolvers[$resolver])) {
            throw new \OutOfBoundsException(
                sprintf('Could not find resolver "%s" for "%s" filter type', $resolver, $filter)
            );
        }

        return $this->resolvers[$resolver];
    }

    /**
     * Gets filtered path for rendering in the browser.
     * It could be the cached one or an url of filter action.
     *
     * @param string      $path          The path where the resolved file is expected
     * @param string      $filter
     * @param array       $runtimeConfig
     * @param string|null $resolver
     *
     * @return string
     */
    public function getBrowserPath(string $path, string $filter, array $runtimeConfig = [], string $resolver = null): string
    {
        $runtimePath = !empty($runtimeConfig) ? $this->getRuntimePath($path, $runtimeConfig) : $path;

        return $this->isStored($runtimePath, $filter, $resolver) ?
            $this->resolve($runtimePath, $filter, $resolver) :
            $this->generateUrl($path, $filter, $runtimeConfig, $resolver);
    }

    /**
     * Get path to runtime config image.
     *
     * @param string $path
     * @param array  $runtimeConfig
     *
     * @return string
     */
    public function getRuntimePath(string $path, array $runtimeConfig): string
    {
        return 'rc/'.$this->signer->sign($path, $runtimeConfig).'/'.$path;
    }

    /**
     * Returns a web accessible URL.
     *
     * @param string      $path          The path where the resolved file is expected
     * @param string      $filter        The name of the imagine filter in effect
     * @param array       $runtimeConfig
     * @param string|null $resolver
     *
     * @return string
     */
    public function generateUrl(string $path, string $filter, array $runtimeConfig = [], string $resolver = null): string
    {
        $parameters = [
            'path' => ltrim($path, '/'),
            'filter' => $filter,
            'resolver' => $resolver,
        ];

        if (!empty($runtimeConfig)) {
            $routeNamed = 'liip_imagine_filter_runtime';
            $parameters = array_merge($parameters, [
                'filters' => $runtimeConfig,
                'hash' => $this->signer->sign($path, $runtimeConfig),
            ]);
        }

        return $this->router->generate($routeNamed ?? 'liip_imagine_filter', array_filter($parameters), UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Checks whether the path is already stored within the respective Resolver.
     *
     * @param string      $path
     * @param string      $filter
     * @param string|null $resolver
     *
     * @return bool
     */
    public function isStored(string $path, string $filter, string $resolver = null): bool
    {
        return $this->getResolver($filter, $resolver)->isStored($path, $filter);
    }

    /**
     * Resolves filtered path for rendering in the browser.
     *
     * @param string      $path
     * @param string      $filter
     * @param string|null $resolver
     *
     * @throws NotFoundHttpException if the path can not be resolved
     *
     * @return string|null The url of resolved image
     */
    public function resolve(string $path, string $filter, string $resolver = null): ?string
    {
        if (false !== strpos($path, '/../') || 0 === strpos($path, '../')) {
            throw new NotFoundHttpException(sprintf("Source image was searched with '%s' outside of the defined root path", $path));
        }

        $preEvent = $this->dispatchEvent(ImagineEvents::PRE_RESOLVE, $path, $filter);
        $resolved = $this->getResolver($preEvent->getFilter(), $resolver)->resolve($preEvent->getPath(), $preEvent->getFilter());

        return $this->dispatchEvent(ImagineEvents::POST_RESOLVE, $preEvent->getPath(), $preEvent->getFilter(), $resolved)->getUrl();
    }

    /**
     * @see ResolverInterface::store
     *
     * @param BinaryInterface $binary
     * @param string          $path
     * @param string          $filter
     * @param string|null     $resolver
     */
    public function store(BinaryInterface $binary, string $path, string $filter, string $resolver = null): void
    {
        $this->getResolver($filter, $resolver)->store($binary, $path, $filter);
    }

    /**
     * @param string|string[]|null $paths
     * @param string|string[]|null $filters
     */
    public function remove($paths = null, $filters = null): void
    {
        $targetPaths = array_filter((array) $paths);
        $filterNames = array_filter((array) ($filters ?? array_keys($this->filterConfig->all())));
        $filterRMaps = new \SplObjectStorage();

        array_walk($filterNames, function (string $filter) use ($filterRMaps) {
            $filterRMaps->attach($r = $this->getResolver($filter, null), array_merge($filterRMaps[$r] ?? [], [$filter]));
        });

        foreach ($filterRMaps as $r) {
            $r->remove($targetPaths, $filterRMaps[$r]);
        }
    }

    /**
     * @param string          $name
     * @param string[]|null[] ...$parameters
     *
     * @return CacheResolveEvent
     */
    private function dispatchEvent(string $name, ...$parameters): CacheResolveEvent
    {
        $this->dispatcher->dispatch($name, $event = new CacheResolveEvent(...$parameters));

        return $event;
    }
}
