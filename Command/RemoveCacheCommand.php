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

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCacheCommand extends Command
{
    use CacheCommandTrait;

    /**
     * @param CacheManager  $cacheManager
     * @param FilterManager $filterManager
     */
    public function __construct(CacheManager $cacheManager, FilterManager $filterManager)
    {
        parent::__construct();

        $this->cacheManager = $cacheManager;
        $this->filterManager = $filterManager;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Remove cache entries for given paths and filters.')
            ->setHelp(static::getCommandHelp())
            ->setDefinition(static::getCommandDefinitions());
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initializeState($input, $output);

        $filters = $this->resolveFilters($input);
        $targets = $this->resolveTargets($input);

        if (0 === count($targets)) {
            $this->doCacheRemoveUseGlobbingTargets($filters);
        } else {
            $this->doCacheRemoveUseExplicitTargets($filters, $targets);
        }

        return $this->getResultCode();
    }


    /**
     * @param string[] $filters
     */
    private function doCacheRemoveUseGlobbingTargets(array $filters)
    {
        foreach ($filters as $f) {
            $this->doCacheRemove($f);
        }

        $this->writeResults($filters, [], true);
    }

    /**
     * @param string[] $filters
     * @param string[] $targets
     */
    private function doCacheRemoveUseExplicitTargets(array $filters, array $targets)
    {
        foreach ($targets as $t) {
            foreach ($filters as $f) {
                $this->doCacheRemove($f, $t);
            }
        }

        $this->writeResults($filters, $targets);
    }

    /**
     * @param string      $target
     * @param string|null $filter
     */
    private function doCacheRemove(string $filter, string $target = null): void
    {
        $this->writeActionStart($filter, $target);

        try {
            if (null === $target) {
                $this->cacheManager->remove(null, $filter);
                $this->writeActionResultDone('removed-glob', true);
            } elseif ($this->cacheManager->isStored($target, $filter)) {
                $this->cacheManager->remove($target, $filter);
                $this->writeActionResultDone('removed', true);
            } else {
                $this->writeActionResultSkip('skipped', true);
            }
        } catch (\Exception $exception) {
            $this->writeActionResultException($exception);
        }
    }

    /**
     * @return string
     */
    private static function getCommandHelp(): string
    {
        return <<<'EOF'
The <comment>%command.name%</comment> command removes the passed image(s) cache entry for the 
resolved filter(s), outputting results using the following basic format:
  <info>image.ext[filter] (removed|skipped|failure)[: (image-path|exception-message)]</>

<comment># bin/console %command.name% --filter=thumb1 foo.ext bar.ext</comment>
Remove cache for <options=bold>both</> <comment>foo.ext</comment> and <comment>bar.ext</comment> images for <options=bold>one</> filter (<comment>thumb1</comment>), outputting:
  <info>- foo.ext[thumb1] removed</>
  <info>- bar.ext[thumb1] removed</>

<comment># bin/console %command.name% --filter=thumb1 --filter=thumb3 foo.ext</comment>
Remove cache for <comment>foo.ext</comment> image using <options=bold>two</> filters (<comment>thumb1</comment> and <comment>thumb3</comment>), outputting:
  <info>- foo.ext[thumb1] removed</>
  <info>- foo.ext[thumb3] removed</>

<comment># bin/console %command.name% foo.ext</comment>
Remove cache for <comment>foo.ext</comment> image using <options=bold>all</> filters (as none were specified), outputting:
  <info>- foo.ext[thumb1] removed</>
  <info>- foo.ext[thumb2] removed</>
  <info>- foo.ext[thumb3] removed</>

EOF;
    }

    /**
     * @return InputDefinition[]
     */
    private static function getCommandDefinitions(): array
    {
        return array_merge(static::getCommandReusableDefinitions(), [
            new InputArgument('paths', InputArgument::IS_ARRAY, 'Image target(s) to run removal on; if none explicitly passed, removal all for passed filters.'),
        ]);
    }
}
