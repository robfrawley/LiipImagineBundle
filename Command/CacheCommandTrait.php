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
use Liip\ImagineBundle\Console\Output\Markup;
use Liip\ImagineBundle\Console\Style\Style;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
     * @var Style
     */
    private $io;

    /**
     * @var bool
     */
    private $machineReadable;

    /**
     * @var int
     */
    private $failures;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function initializeState(InputInterface $input, OutputInterface $output): void
    {
        $this->machineReadable = $input->getOption('machine-readable');
        $this->io = new Style($output, !$this->machineReadable ? !$input->getOption('no-colors') : false);
        $this->failures = 0;

        $this->writeHeader();
    }

    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function resolveTargets(InputInterface $input): array
    {
        return $input->getArgument('paths');
    }

    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function resolveFilters(InputInterface $input): array
    {
        return $this->normalizeFilterList($input->getOption('filter'));
    }

    /**
     * @param string[] $filters
     *
     * @return string[]
     */
    private function normalizeFilterList(array $filters): array
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
     * @return void
     */
    private function writeHeader(): void
    {
        if (!$this->machineReadable) {
            $this->io->titles()(str_replace('liip:imagine:', '', $this->getName()), 'liip/imagine-bundle');
        }
    }

    /**
     * @param string      $filter
     * @param string|null $target
     */
    protected function writeActionStart($filter, $target = null)
    {
        if (!$this->machineReadable) {
            $this->io->text(' - ');
        }

        $this->io->group($target ?: '*', $filter, new Markup('blue'));
        $this->io->space();
    }

    /**
     * @param string      $result
     * @param bool        $newline
     * @param string|null $color
     */
    protected function writeActionResult(string $result, bool $newline = false, string $color = null): void
    {
        $this->io->status($result, new Markup($color ?? 'default'));

        if ($newline) {
            $this->io->newline();
        }
    }

    /**
     * @param string|null $result
     * @param bool        $newline
     */
    protected function writeActionResultDone(string $result = null, $newline = false): void
    {
        $this->writeActionResult($result ?? 'done', $newline, 'green');
    }

    /**
     * @param string|null $result
     * @param bool        $newline
     */
    protected function writeActionResultSkip(string $result = null, $newline = false): void
    {
        $this->writeActionResult($result ?? 'skipped', $newline, 'yellow');
    }

    /**
     * @param string|null $result
     * @param bool        $newline
     */
    protected function writeActionResultFail(string $result = null, $newline = false): void
    {
        $this->writeActionResult($result ?? 'failed', $newline, 'red');
    }

    /**
     * @param \Exception $exception
     */
    protected function writeActionResultException(\Exception $exception): void
    {
        $this->writeActionResultFail();
        $this->writeActionResultDetail($exception->getMessage());

        ++$this->failures;
    }

    /**
     * @param string $detail
     */
    protected function writeActionResultDetail(string $detail): void
    {
        $this->io->text(' %s', $detail);
        $this->io->newline();
    }

    /**
     * @param string[] $filters
     * @param string[] $targets
     * @param bool     $glob
     */
    private function writeResults(array $filters, array $targets, $glob = false): void
    {
        if ($this->machineReadable) {
            return;
        }

        $outputFormat = 'Completed %d %s (%d %s, %s %s)';
        $targetLength = count($targets);
        $filterLength = count($filters);
        $actionLength = ($glob ? $filterLength : ($filterLength * $targetLength)) - $this->failures;
        $actionString = $this->getPluralized($actionLength, $this->resolveCommandOperationName());

        $replacements = [
            $actionLength,
            $actionString,
            $filterLength,
            $this->getPluralized($filterLength, 'filter'),
            $glob ? '?' : $targetLength,
            $this->getPluralized($targetLength, 'image'),
        ];

        if ($this->failures) {
            $this->io->blocks()->crit($outputFormat.' [encountered %d failures]', ...array_merge($replacements, [$this->failures]));
        } else {
            $this->io->blocks()->okay($outputFormat, ...$replacements);
        }
    }

    /**
     * @return string
     */
    private function resolveCommandOperationName(): string
    {
        if (false !== strpos(__CLASS__, 'Remove')) {
            return 'removal';
        }

        if (false !== strpos(__CLASS__, 'Resolve')) {
            return 'resolution';
        }

        return 'operation';
    }

    /**
     * @param int    $count
     * @param string $singular
     *
     * @return string
     */
    private function getPluralized(int $count, string $singular): string
    {
        return 1 === $count ? $singular : sprintf('%ss', $singular);
    }

    /**
     * @return int
     */
    private function getResultCode(): int
    {
        return 0 === $this->failures ? 0 : 255;
    }

    /**
     * @return InputDefinition[]
     */
    private static function getCommandReusableDefinitions(): array
    {
        return [
            new InputOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter(s) to use for image operation; if none explicitly passed, use all configured filters.'),
            new InputOption('no-colors', 'C', InputOption::VALUE_NONE, 'Write only un-styled text output; remove any colors or other formatting.'),
            new InputOption('machine-readable', 'm', InputOption::VALUE_NONE, 'Write only machine-readable output; silences extra verbose reporting and implies --no-colors.'),
        ];
    }
}