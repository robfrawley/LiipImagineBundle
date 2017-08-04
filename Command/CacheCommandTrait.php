<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Command;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Component\Console\Style\ImagineStyle;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
trait CacheCommandTrait
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var ImagineStyle
     */
    private $io;

    /**
     * @var bool
     */
    private $isDecorated;

    /**
     * @var bool
     */
    private $asScript;

    /**
     * @var int
     */
    private $failures = 0;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function setupOutputStyle(InputInterface $input, OutputInterface $output): void
    {
        $this->asScript = $input->getOption('as-script');
        $this->isDecorated = $this->asScript ? false : !$input->getOption('no-colors');
        $this->io = new ImagineStyle($input, $output, $this->isDecorated);
    }

    /**
     * @param InputInterface $input
     *
     * @return array[]
     */
    private function resolveFiltersAndPaths(InputInterface $input): array
    {
        return [
            $input->getArgument('path'),
            $this->resolveFilters($input->getOption('filter')),
        ];
    }

    /**
     * @param string[] $filters
     *
     * @return string[]
     */
    private function resolveFilters(array $filters): array
    {
        if (0 < count($filters)) {
            return $filters;
        }

        if (0 < count($filters = array_keys((array) $this->filterManager->getFilterConfiguration()->all()))) {
            return $filters;
        }

        throw new RuntimeException('No filters have been defined in the active configuration!');
    }

    /**
     * @param string $context
     *
     * @return CacheCommandTrait
     */
    private function writeCommandHeader(string $context): self
    {
        if (!$this->asScript) {
            $this->io->title(sprintf('[liip/imagine-bundle] %s', $context));
        }

        return $this;
    }

    /**
     * @param string[] $images
     * @param string[] $filters
     *
     * @return CacheCommandTrait
     */
    private function writeCommandResult(array $images, array $filters): self
    {
        if ($this->asScript) {
            return $this;
        }

        $countImagePaths = count($images);
        $countFilterSets = count($filters);
        $countAllActions = ($countFilterSets * $countImagePaths) - $this->failures;
        $pluralizeString = function (int $count, string $singular, string $plural = null) {
            return 1 === $count ? $singular : ($plural ?: sprintf('%ss', $singular));
        };

        if (false !== strpos(__CLASS__, 'Remove')) {
            $wordsAllActions = $pluralizeString($countAllActions, 'removal', 'removals');
        } elseif (false !== strpos(__CLASS__, 'Resolve')) {
            $wordsAllActions = $pluralizeString($countAllActions, 'resolution');
        } else {
            $wordsAllActions = $pluralizeString($countAllActions, 'operation');
        }

        $replacements = [
            $countAllActions,
            $wordsAllActions,
            $countImagePaths,
            $pluralizeString($countImagePaths, 'image'),
            $countFilterSets,
            $pluralizeString($countFilterSets, 'filter'),
        ];

        if ($this->failures) {
            $this->io->error('Completed %d %s (%d %s, %d %s) %s', array_merge($replacements, [
                sprintf('[encountered %d failures]', $this->failures)
            ]));
        } else {
            $this->io->success('Completed %d %s (%d %s, %d %s)', $replacements);
        }

        return $this;
    }

    /**
     * @param string $image
     * @param string $filter
     */
    private function writeItemInit(string $image, string $filter): void
    {
        if (!$this->asScript) {
            $this->io->text(' - ');
        }

        $this->io->text('<fg=blue;options=bold>%s[<fg=blue>%s</><fg=blue;options=bold>]</> ', [$image, $filter]);
    }

    /**
     * @param string $status
     * @param string $color
     */
    private function writeItemStat(string $status, string $color): void
    {
        $this->io->text(sprintf('<fg=%s;options=bold>(%s)</>', $color, $status));
    }

    /**
     * @param string $message
     */
    private function writeItemDesc(string $message = ''): void
    {
        $this->io->line(' %s', [$message]);
    }

    /**
     * @return int
     */
    private function getResultCode(): int
    {
        return 0 === $this->failures ? 0 : 255;
    }
}
