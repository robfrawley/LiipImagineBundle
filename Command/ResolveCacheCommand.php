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
    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $forceResolution;

    /**
     * @var bool
     */
    private $machineReadable;

    /**
     * @var bool
     */
    private $verbosityQuiet;

    /**
     * @var int
     */
    private $resolveFailures = 0;

    /**
     * @param DataManager   $dataManager
     * @param FilterManager $filterManager
     * @param CacheManager  $cacheManager
     */
    public function __construct(DataManager $dataManager, FilterManager $filterManager, CacheManager $cacheManager)
    {
        parent::__construct();

        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->cacheManager = $cacheManager;
    }

    protected function configure()
    {
        $this
            ->setName('liip:imagine:cache:resolve')
            ->setDescription('Resolve cache for given path and set of filters.')
            ->addArgument('paths', InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Any number of image paths to act on.')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'List of filters to apply to passed images.')
            ->addOption('force', 'F', InputOption::VALUE_NONE,
                'Force image resolution regardless of cache.')
            ->addOption('machine', 'm', InputOption::VALUE_NONE,
                'Only print machine-parseable results.')
            ->setHelp(<<<'EOF'
The <comment>%command.name%</comment> command resolves the passed image(s) for the resolved
filter(s), outputting results using the following basic format:
  <info>- "image.ext[filter]" (resolved|cached|failed) as "path/to/cached/image.ext"</>

<comment># bin/console %command.name% --filter=thumb1 foo.ext bar.ext</comment>
Resolve <options=bold>both</> <comment>foo.ext</comment> and <comment>bar.ext</comment> using <comment>thumb1</comment> filter, outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>
  <info>- "bar.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/bar.ext"</>

<comment># bin/console %command.name% --filter=thumb1 --filter=thumb2 foo.ext</comment>
Resolve <comment>foo.ext</comment> using <options=bold>both</> <comment>thumb1</comment> and <comment>thumb2</comment> filters, outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>
  <info>- "foo.ext[thumb2]" resolved as "http://localhost/media/cache/thumb2/foo.ext"</>

<comment># bin/console %command.name% foo.ext</comment>
Resolve <comment>foo.ext</comment> using <options=bold>all configured filters</> (as none are specified), outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>
  <info>- "foo.ext[thumb2]" resolved as "http://localhost/media/cache/thumb2/foo.ext"</>

<comment># bin/console %command.name% --force --filter=thumb1 foo.ext</comment>
Resolve <comment>foo.ext</comment> using <comment>thumb1</comment> and <options=bold>force creation</> regardless of cache, outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>

EOF
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->forceResolution = $input->getOption('force');
        $this->machineReadable = $input->getOption('machine');
        $this->verbosityQuiet = $output->isQuiet();

        if (0 === count($filters = $input->getOption('filters'))) {
            $filters = array_keys((array) $this->filterManager->getFilterConfiguration()->all());
        }

        $this->outputTitle('Resolving Imagine Bundle Images');

        $images = $input->getArgument('paths');

        foreach ($images as $i) {
            foreach ($filters  as $f) {
                $this->resolvePathWithFilter($i, $f);
            }
        }

        $this->outputSummary($filters, $images);

        return $this->getReturnCode();
    }

    /**
     * @param string $path
     * @param string $filter
     */
    private function resolvePathWithFilter(string $path, string $filter): void
    {
        $this->writeText('- "%s[%s]" ', array($path, $filter));

        try {
            if ($this->forceResolution || !$this->cacheManager->isStored($path, $filter)) {
                $this->cacheManager->store($this->filterManager->applyFilter($this->dataManager->find($filter, $path), $filter), $path, $filter);
                $this->writeText('resolved ');
            } else {
                $this->writeText('cached ');
            }

            $this->writeLine('as "%s"', array($this->cacheManager->resolve($path, $filter)));
        } catch (\Exception $e) {
            $this->writeLine('failed as "%s"', array($e->getMessage()));
            ++$this->resolveFailures;
        }
    }

    /**
     * @param string $title
     */
    private function outputTitle(string $title): void
    {
        if ($this->machineReadable) {
            return;
        }

        $this
            ->writeLine('<info>%s</info>', array($title))
            ->writeLine(str_repeat('=', strlen($title)))
            ->writeLine();
   }

    /**
     * @param string[] $filters
     * @param string[] $images
     */
    private function outputSummary(array $filters, array $images): void
    {
        if (!$this->machineReadable) {
            return;
        }

        $countUsedImg = count($images);
        $countFilters = count($filters);
        $countActions = $countFilters * $countUsedImg;

        $this
            ->writeLine()
            ->writeLine('Completed %d %s (%d %s on %d %s) <fg=red>%s</>', [
                $countActions,
                1 === $countActions ? 'action' : 'actions',
                count($filters),
                1 === $countFilters ? 'filter' : 'filters',
                count($images),
                1 === $countActions ? 'image' : 'images',
                0 === $this->resolveFailures ? '' : sprintf('[encountered %d failures', $this->resolveFailures)
            ]
        );
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @return self
     */
    private function writeText(string $format = '', array $replacements = []): self
    {
        if (!$this->verbosityQuiet) {
            $this->output->write(vsprintf($format, $replacements));
        }

        return $this;
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @return self
     */
    private function writeLine($format = '', array $replacements = array()): self
    {
        if (!$this->verbosityQuiet) {
            $this->output->writeln(vsprintf($format, $replacements));
        }

        return $this;
    }

    /**
     * @return int
     */
    private function getReturnCode(): int
    {
        return 0 === $this->resolveFailures ? 0 : 255;
    }
}
