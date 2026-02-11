<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Command;

use Cecil\Builder;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build command.
 *
 * This command generates the website in the output directory.
 * It can include drafts, optimize generated files, and perform a dry run.
 * It also allows building a specific page or a subset of pages, clearing the cache, and showing build metrics.
 */
class Build extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds the website')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                new InputOption('baseurl', 'u', InputOption::VALUE_REQUIRED, 'Set the base URL'),
                new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Set the output directory'),
                new InputOption('optimize', null, InputOption::VALUE_NEGATABLE, 'Enable (or disable --no-optimize) optimization of generated files'),
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Build without saving'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to extra config files (comma-separated)'),
                new InputOption('clear-cache', null, InputOption::VALUE_OPTIONAL, 'Clear cache before build (optional cache key as regular expression)', false),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Build a specific page'),
                new InputOption('render-subset', null, InputOption::VALUE_REQUIRED, 'Render a subset of pages'),
                new InputOption('show-pages', null, InputOption::VALUE_NONE, 'Show list of built pages in a table'),
                new InputOption('metrics', 'm', InputOption::VALUE_NONE, 'Show build metrics (duration and memory) of each step'),
                new InputOption('notif', null, InputOption::VALUE_NONE, 'Send desktop notification on build completion'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command generates the website in the <comment>output</comment> directory.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>
  <info>%command.full_name% --baseurl=https://example.com/</>
  <info>%command.full_name% --output=_site</>

To build the website with <comment>optimization</comment> of generated files, you can use the <info>--optimize</info> option.
This is useful to reduce the size of the generated files and <comment>improve performance</comment>:

  <info>%command.full_name% --optimize</>
  <info>%command.full_name% --no-optimize</>

To build the website <comment>without overwriting files in the output</comment> directory, you can use the <info>--dry-run</info> option.
This is useful to check what would be built without actually writing files:

  <info>%command.full_name% --dry-run</>

To build the website with a specific subset of rendered pages, you can use the <info>--render-subset</info> option.
This is useful to <comment>build only a part of the website</comment>, for example, only "hot" pages or a specific section:

  <info>%command.full_name% --render-subset=subset</>

To show build steps <comment>metrics</comment>, run:

  <info>%command.full_name% --metrics</>

Send a desktop <comment>notification</comment> on build completion, run:

  <info>%command.full_name% --notif</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = [];
        $options = [];
        $messageOpt = '';

        if ($input->getOption('baseurl')) {
            $config['baseurl'] = $input->getOption('baseurl');
        }
        if ($input->getOption('output')) {
            $config['output']['dir'] = $input->getOption('output');
            if ($input->getOption('output') != self::SERVE_OUTPUT) {
                Util\File::getFS()->dumpFile(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'output'), (string) $input->getOption('output'));
            }
        }
        if ($input->getOption('optimize') === true) {
            $config['optimize']['enabled'] = true;
        }
        if ($input->getOption('optimize') === false) {
            $config['optimize']['enabled'] = false;
        }
        if ($input->getOption('clear-cache') === null) {
            $config['cache']['enabled'] = false;
        }

        $builder = $this->getBuilder($config);

        if ($input->getOption('drafts')) {
            $options['drafts'] = true;
            $messageOpt .= ' with drafts';
        }
        if ($input->getOption('dry-run')) {
            $options['dry-run'] = true;
            $messageOpt .= ' (dry-run)';
        }
        if ($input->getOption('page')) {
            $options['page'] = $input->getOption('page');
        }
        if ($input->getOption('render-subset')) {
            $options['render-subset'] = (string) $input->getOption('render-subset');
        }
        if ($input->getOption('clear-cache')) {
            if (0 < $removedFiles = (new \Cecil\Cache($this->getBuilder()))->clearByPattern((string) $input->getOption('clear-cache'))) {
                $output->writeln(\sprintf('<info>%s cache files removed by regular expression "%s"</info>', $removedFiles, $input->getOption('clear-cache')));
            }
        }

        $output->writeln(\sprintf('Building website%s...', $messageOpt));
        $output->writeln(\sprintf('<comment>Path:   %s</comment>', $this->getPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
        if (!empty($this->getConfigFiles())) {
            $output->writeln(\sprintf('<comment>Config: %s</comment>', implode(', ', $this->getConfigFiles())), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        $output->writeln(\sprintf('<comment>Output: %s</comment>', $this->getBuilder()->getConfig()->getOutputPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
        if ($builder->getConfig()->isEnabled('cache') !== false) {
            $output->writeln(\sprintf('<comment>Cache:  %s</comment>', $builder->getConfig()->getCachePath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        // build
        $builder->build($options);
        $output->writeln('Build done');

        // notification
        if ($input->getOption('notif')) {
            $this->notification('Build done 🎉');
        }

        // show build steps metrics
        if ($input->getOption('metrics')) {
            $table = new Table($output);
            $table
                ->setHeaderTitle('Build steps metrics')
                ->setHeaders(['Step', 'Duration', 'Memory'])
                ->setRows($builder->getMetrics()['steps'])
            ;
            $table->setStyle('box')->render();
        }

        // show built pages as table
        if ($input->getOption('show-pages')) {
            $pagesAsArray = [];
            foreach (
                $this->getBuilder()->getPages()->filter(function (\Cecil\Collection\Page\Page $page) {
                    return $page->getVariable('published');
                })->usort(function (\Cecil\Collection\Page\Page $pageA, \Cecil\Collection\Page\Page $pageB) {
                    return strnatcmp((string) $pageA['language'], (string) $pageB['language']);
                }) as $page
            ) {
                /** @var \Cecil\Collection\Page\Page $page */
                $pagesAsArray[] = [
                    $page->getId(),
                    $page->getVariable('language'),
                    \sprintf("%s %s", $page->getType(), $page->getType() !== \Cecil\Collection\Page\Type::PAGE->value ? "(" . \count($page->getPages() ?: []) . ")" : ''),
                    $page->getSection(),
                    $page->isVirtual() ? 'true' : 'false',
                ];
            }
            $table = new Table($output);
            $table
                ->setHeaderTitle(\sprintf("Built pages (%s)", \count($pagesAsArray)))
                ->setHeaders(['ID', 'Lang', 'Type', 'Section', 'Virtual'])
                ->setRows($pagesAsArray)
            ;
            $table->setStyle('box')->render();
        }

        return Command::SUCCESS;
    }
}
