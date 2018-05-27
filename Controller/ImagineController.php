<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Controller;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Config\Controller\ControllerConfig;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\SignerInterface;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImagineController
{
    /**
     * @var FilterService
     */
    private $filterService;

    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @var SignerInterface
     */
    private $signer;

    /**
     * @var ControllerConfig
     */
    private $controllerConfig;

    /**
     * @param FilterService         $filterService
     * @param DataManager           $dataManager
     * @param SignerInterface       $signer
     * @param ControllerConfig|null $controllerConfig
     */
    public function __construct(FilterService $filterService, DataManager $dataManager, SignerInterface $signer, ?ControllerConfig $controllerConfig = null)
    {
        $this->filterService = $filterService;
        $this->dataManager = $dataManager;
        $this->signer = $signer;

        if (null === $controllerConfig) {
            @trigger_error(sprintf(
                'Instantiating "%s" without a forth argument of type "%s" is deprecated since 2.1.0 and will be required in 3.0.', self::class, ControllerConfig::class
            ), E_USER_DEPRECATED);
        }

        $this->controllerConfig = $controllerConfig ?? new ControllerConfig(301);
    }

    /**
     * This action applies a given filter to a given image, saves the image and redirects the browser to the stored
     * image.
     *
     * The resulting image is cached so subsequent requests will redirect to the cached image instead applying the
     * filter and storing the image again.
     *
     * @param Request $request
     * @param string  $path
     * @param string  $filter
     *
     * @throws RuntimeException
     * @throws NotFoundHttpException
     *
     * @return RedirectResponse
     */
    public function filterAction(Request $request, $path, $filter)
    {
        $resolver = $request->get('resolver');
        $path = urldecode($path);

        return $this->createRedirectResponse(function () use ($path, $filter, $resolver) {
            return $this->filterService->getUrlOfFilteredImage($path, $filter, $resolver);
        }, $path, $filter);
    }

    /**
     * This action applies a given filter -merged with additional runtime filters- to a given image, saves the image and
     * redirects the browser to the stored image.
     *
     * The resulting image is cached so subsequent requests will redirect to the cached image instead applying the
     * filter and storing the image again.
     *
     * @param Request $request
     * @param string  $hash
     * @param string  $path
     * @param string  $filter
     *
     * @throws RuntimeException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     *
     * @return RedirectResponse
     */
    public function filterRuntimeAction(Request $request, $hash, $path, $filter)
    {
        $resolver = $request->get('resolver');
        $path = urldecode($path);
        $runtimeConfig = $request->query->get('filters', []);

        if (!is_array($runtimeConfig)) {
            throw new NotFoundHttpException(sprintf('Filters must be an array. Value was "%s"', $runtimeConfig));
        }

        if (true !== $this->signer->check($hash, $path, $runtimeConfig)) {
            throw new BadRequestHttpException(sprintf(
                'Signed url does not pass the sign check for path "%s" and filter "%s" and runtime config %s',
                $path,
                $filter,
                json_encode($runtimeConfig)
            ));
        }

        return $this->createRedirectResponse(function () use ($path, $filter, $runtimeConfig, $resolver) {
            return $this->filterService->getUrlOfFilteredImageWithRuntimeFilters($path, $filter, $runtimeConfig, $resolver);
        }, $path, $filter, $hash);
    }

    /**
     * @param \Closure    $url
     * @param string      $path
     * @param string      $filter
     * @param null|string $hash
     *
     * @return RedirectResponse
     */
    private function createRedirectResponse(\Closure $url, string $path, string $filter, ?string $hash = null): RedirectResponse
    {
        try {
            return new RedirectResponse($url(), $this->controllerConfig->getRedirectResponseCode());
        } catch (NotLoadableException $exception) {
            if (null !== $this->dataManager->getDefaultImageUrl($filter)) {
                return new RedirectResponse($this->dataManager->getDefaultImageUrl($filter));
            }

            throw new NotFoundHttpException(sprintf('Source image for path "%s" could not be found', $path));
        } catch (NonExistingFilterException $exception) {
            throw new NotFoundHttpException(sprintf('Requested non-existing filter "%s"', $filter));
        } catch (RuntimeException $exception) {
            throw new \RuntimeException(vsprintf('Unable to create image for path "%s" and filter "%s". Message was "%s"', [
                $hash ? sprintf('%s/%s', $hash, $path) : $path,
                $filter,
                $exception->getMessage(),
            ]), 0, $exception);
        }
    }
}
