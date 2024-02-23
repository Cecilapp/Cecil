<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Util;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds the website.
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
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to extra config files (comma-separated)'),
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Build a specific page'),
                    new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Build without saving'),
                    new InputOption('baseurl', null, InputOption::VALUE_REQUIRED, 'Set the base URL'),
                    new InputOption('output', null, InputOption::VALUE_REQUIRED, 'Set the output directory'),
                    new InputOption('optimize', null, InputOption::VALUE_OPTIONAL, 'Optimize files (disable with "no")', false),
                    new InputOption('clear-cache', null, InputOption::VALUE_OPTIONAL, 'Clear cache before build (optional cache key regular expression)', false),
                    new InputOption('show-pages', null, InputOption::VALUE_NONE, 'Show built pages as table'),
                ])
            )
            ->setHelp('Builds the website in the output directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [];
        $options = [];
        $messageOpt = '';

        if ($input->getOption('baseurl')) {
            $config['baseurl'] = $input->getOption('baseurl');
        }
        if ($input->getOption('output')) {
            $config['output']['dir'] = $input->getOption('output');
            Util\File::getFS()->dumpFile(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'), (string) $input->getOption('output'));
        }
        if ($input->getOption('optimize') === null) {
            $config['optimize']['enabled'] = true;
        }
        if ($input->getOption('optimize') == 'no') {
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
        if ($input->getOption('clear-cache')) {
            if (0 < $removedFiles = (new \Cecil\Assets\Cache($this->getBuilder()))->clearByPattern((string) $input->getOption('clear-cache'))) {
                $output->writeln(sprintf('<info>%s cache files removed by regular expression "%s"</info>', $removedFiles, $input->getOption('clear-cache')));
            }
        }

        $output->writeln(sprintf('Building website%s...', $messageOpt));
        $output->writeln(
            sprintf('<comment>Path: %s</comment>', $this->getPath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        if (!empty($this->getConfigFiles())) {
            $output->writeln(
                sprintf('<comment>Config: %s</comment>', implode(', ', $this->getConfigFiles())),
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
        if ((bool) $builder->getConfig()->get('cache.enabled')) {
            $output->writeln(
                sprintf('<comment>Cache: %s</comment>', $builder->getConfig()->getCachePath()),
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $builder->build($options);
        $output->writeln('Done 🎉');

        if ($input->getOption('show-pages')) {
            $pagesAsArray = [];
            foreach (
                $this->getBuilder()->getPages()->filter(function (\Cecil\Collection\Page\Page $page) {
                    return $page->getVariable('published');
                }) as $page
            ) {
                $pagesAsArray[] = [
                    $page->getId(),
                    $page->getVariable('language'),
                    sprintf("%s %s", $page->getType(), $page->getType() !== \Cecil\Collection\Page\Type::PAGE->value ? "(" . \count($page->getPages() ?: []) . ")" : ''),
                    $page->getParent()?->getId(),
                    $page->isVirtual() ? 'False' : 'true',
                ];
            }
            $table = new Table($output);
            $table
                ->setHeaderTitle(sprintf("Built pages (%s)", \count($pagesAsArray)))
                ->setHeaders(['ID', 'Lang', 'Type', 'Parent', 'File'])
                ->setRows($pagesAsArray)
            ;
            $table->setStyle('box')->render();
        }

        return 0;
    }
}
