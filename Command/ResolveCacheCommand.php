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
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveCacheCommand extends Command
{
    use CacheCommandTrait;

    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @param CacheManager  $cacheManager
     * @param FilterManager $filterManager
     * @param DataManager   $dataManager
     */
    public function __construct(CacheManager $cacheManager, FilterManager $filterManager, DataManager $dataManager)
    {
        parent::__construct();

        $this->cacheManager = $cacheManager;
        $this->filterManager = $filterManager;
        $this->dataManager = $dataManager;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Resolve cache entries for the given paths and filters.')
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

        $forced = $input->getOption('force');
        $filters = $this->resolveFilters($input);
        $targets = $this->resolveTargets($input);

        $this->doCacheResolveAsFiltersAndTargets($filters, $targets, $forced);

        return $this->getResultCode();
    }

    /**
     * @param string[] $filters
     * @param string[] $targets
     * @param bool     $forced
     */
    private function doCacheResolveAsFiltersAndTargets(array $filters, array $targets, bool $forced)
    {
        foreach ($targets as $t) {
            foreach ($filters as $f) {
                $this->doCacheResolve($f, $t, $forced);
            }
        }

        $this->writeResults($filters, $targets);
    }

    /**
     * @param string $filter
     * @param string $target
     * @param bool   $forced
     *
     * @return void
     */
    private function doCacheResolve(string $filter, string $target, bool $forced): void
    {
        $this->writeActionStart($filter, $target);

        try {
            if ($forced || !$this->cacheManager->isStored($target, $filter)) {
                $this->cacheManager->store($this->filterManager->applyFilter($this->dataManager->find($filter, $target), $filter), $target, $filter);
                $this->writeActionResultDone('resolved');
            } else {
                $this->writeActionResultSkip('cached');
            }

            $this->writeActionResultDetail($this->cacheManager->resolve($target, $filter));
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
The <comment>%command.name%</comment> command resolves the passed image(s) for the resolved
filter(s), outputting results using the following basic format:
  <info>image.ext[filter] (resolved|cached|failed): (resolve-image-path|exception-message)</>

<comment># bin/console %command.name% --filter=thumb1 foo.ext bar.ext</comment>
Resolve <options=bold>both</> <comment>foo.ext</comment> and <comment>bar.ext</comment> images using <options=bold>one</> filter (<comment>thumb1</comment>), outputting:
  <info>- foo.ext[thumb1] status: http://localhost/media/cache/thumb1/foo.ext</>
  <info>- bar.ext[thumb1] status: http://localhost/media/cache/thumb1/bar.ext</>

<comment># bin/console %command.name% --filter=thumb1 --filter=thumb3 foo.ext</comment>
Resolve <comment>foo.ext</comment> image using <options=bold>two</> filters (<comment>thumb1</comment> and <comment>thumb3</comment>), outputting:
  <info>- foo.ext[thumb1] status: http://localhost/media/cache/thumb1/foo.ext</>
  <info>- foo.ext[thumb3] status: http://localhost/media/cache/thumb3/foo.ext</>

<comment># bin/console %command.name% foo.ext</comment>
Resolve <comment>foo.ext</comment> image using <options=bold>all</> filters (as none were specified), outputting:
  <info>- foo.ext[thumb1] status: http://localhost/media/cache/thumb1/foo.ext</>
  <info>- foo.ext[thumb2] status: http://localhost/media/cache/thumb2/foo.ext</>
  <info>- foo.ext[thumb3] status: http://localhost/media/cache/thumb2/foo.ext</>

<comment># bin/console %command.name% --force --filter=thumb1 foo.ext</comment>
Resolve <comment>foo.ext</comment> image using <options=bold>one</> filter (<comment>thumb1</comment>) and <options=bold>forcing resolution</> (regardless of cache), outputting:
  <info>- foo.ext[thumb1] resolved: http://localhost/media/cache/thumb1/foo.ext</>

EOF;
    }

    /**
     * @return array
     */
    private static function getCommandDefinitions(): array
    {
        return array_merge(static::getCommandReusableDefinitions(), [
            new InputOption('force', 'F', InputOption::VALUE_NONE, 'Force re-resolution of image, regardless of whether it has been previously cached.'),
            new InputArgument('paths', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Image target(s) to run resolution on.'),
        ]);
    }
}