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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveCacheCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('liip:imagine:cache:resolve')
            ->setDescription('Resolve cache for given path and set of filters.')
            ->setDefinition(array(
                new InputArgument('paths', InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                    'Any number of image paths to act on.'),
                new InputOption('filters', ['f'], InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                    'List of filters to apply to passed images.'),
                new InputOption('force', ['F'], InputOption::VALUE_NONE,
                    'Force image resolution regardless of cache.'),
                new InputOption('machine', ['m'], InputOption::VALUE_NONE,
                    'Only print machine-parseable results.'),
            ))
            ->setHelp(<<<'EOF'
The <comment>%command.name%</comment> command resolves the passed image(s) for the resolved
filter(s), outputting results using the following basic format:
  <info>- "image.ext[filter]" (resolved|cached) as "path/to/cached/image.ext"</>


<comment># bin/console %command.name% --filters=thumb1 foo.ext bar.ext</comment>
Resolve <options=bold>both</> <comment>foo.ext</comment> and <comment>bar.ext</comment> using <comment>thumb1</comment> filter, outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>
  <info>- "bar.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/bar.ext"</>


<comment># bin/console %command.name% --filters=thumb1 --filters=thumb2 foo.ext</comment>
Resolve <comment>foo.ext</comment> using <options=bold>both</> <comment>thumb1</comment> and <comment>thumb2</comment> filters, outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>
  <info>- "foo.ext[thumb2]" resolved as "http://localhost/media/cache/thumb2/foo.ext"</>


<comment># bin/console %command.name% foo.ext</comment>
Resolve <comment>foo.ext</comment> using <options=bold>all configured filters</> (as none are specified), outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>
  <info>- "foo.ext[thumb2]" resolved as "http://localhost/media/cache/thumb2/foo.ext"</>


<comment># bin/console %command.name% --force --filters=thumb1 foo.ext</comment>
Resolve <comment>foo.ext</comment> using <comment>thumb1</comment> and <options=bold>force creation</> regardless of cache, outputting:
  <info>- "foo.ext[thumb1]" resolved as "http://localhost/media/cache/thumb1/foo.ext"</>

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $imagePaths = $input->getArgument('paths');
        $useFilters = $input->getOption('filters');
        $forced = $input->getOption('force');
        $failures = 0;

        $filterManager = $this->getFilterManager();
        $dataManager = $this->getDataManager();
        $cacheManager = $this->getCacheManager();

        if (0 === count($useFilters)) {
            $useFilters = array_keys($filterManager->getFilterConfiguration()->all());
        }

        $this->outputTitle($output);

        foreach ($imagePaths as $path) {
            foreach ($useFilters as $filter) {
                $output->write(sprintf('- "%s[%s]" ', $path, $filter));

                try {
                    if ($forced || !$cacheManager->isStored($path, $filter)) {
                        $cacheManager->store($filterManager->applyFilter($dataManager->find($filter, $path), $filter), $path, $filter);
                        $output->write('RESOLVED ');
                    } else {
                        $output->write('CACHED ');
                    }

                    $output->writeln(sprintf('as "%s"', $cacheManager->resolve($path, $filter)));
                } catch (\Exception $e) {
                    $output->writeln(sprintf('FAILED with exception "%s"', $e->getMessage()));
                    ++$failures;
                }
            }
        }

        $this->outputSummary($output, $useFilters, $imagePaths, $failures);

        return 0 === $failures ? 0 : 255;
    }

    /**
     * @param OutputInterface $output
     */
    private function outputTitle(OutputInterface $output)
    {
        $title = 'Resolving Imagine Bundle Images';
        $output->writeln(sprintf('<info>%s</info>', $title));
        $output->writeln(str_repeat('=', strlen($title)));
        $output->writeln('');
    }

    /**
     * @param OutputInterface $output
     * @param string[]        $useFilters
     * @param string[]        $imagePaths
     * @param int             $failures
     */
    private function outputSummary(OutputInterface $output, $useFilters, $imagePaths, $failures)
    {
        $useFiltersCount = count($useFilters);
        $imagePathsCount = count($imagePaths);
        $totalActionStep = $useFiltersCount * $imagePathsCount;

        $output->writeln('');
        $output->writeln(vsprintf('Completed %d %s (%d %s on %d %s) %s', [
            $totalActionStep,
            1 === $totalActionStep ? 'operation' : 'operations',
            count($useFilters),
            1 === $useFiltersCount ? 'filter' : 'filters',
            count($imagePaths),
            1 === $totalActionStep ? 'image' : 'images',
            0 === $failures ? '' : sprintf('<fg=red>[encountered</> <fg=red;options=bold>%d</> <fg=red> failures]</>', $failures)
        ]));
    }

    /**
     * @return FilterManager
     */
    private function getFilterManager()
    {
        return $this->getContainer()->get('liip_imagine.filter.manager');
    }

    /**
     * @return DataManager
     */
    private function getDataManager()
    {
        return $this->getContainer()->get('liip_imagine.data.manager');
    }

    /**
     * @return CacheManager
     */
    private function getCacheManager()
    {
        return $this->getContainer()->get('liip_imagine.cache.manager');
    }
}
