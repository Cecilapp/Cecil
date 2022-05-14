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

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Yosymfony\ResourceWatcher\Crc32ContentHash;
use Yosymfony\ResourceWatcher\ResourceCacheMemory;
use Yosymfony\ResourceWatcher\ResourceWatcher;

/**
 * Starts the built-in server.
 */
class Serve extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('serve')
            ->setDescription('Starts the built-in server')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to extra config files (comma-separated)'),
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Build a specific page'),
                    new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open web browser automatically'),
                    new InputOption('host', null, InputOption::VALUE_REQUIRED, 'Server host'),
                    new InputOption('port', null, InputOption::VALUE_REQUIRED, 'Server port'),
                    new InputOption('postprocess', null, InputOption::VALUE_OPTIONAL, 'Post-process output (disable with "no")', false),
                    new InputOption('clear-cache', null, InputOption::VALUE_NONE, 'Clear cache before build'),
                ])
            )
            ->setHelp('Starts the live-reloading-built-in web server');
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drafts = $input->getOption('drafts');
        $open = $input->getOption('open');
        $host = $input->getOption('host') ?? 'localhost';
        $port = $input->getOption('port') ?? '8000';
        $postprocess = $input->getOption('postprocess');
        $clearcache = $input->getOption('clear-cache');
        $verbose = $input->getOption('verbose');
        $page = $input->getOption('page');

        $this->setUpServer($host, $port);

        $phpFinder = new PhpExecutableFinder();
        $php = $phpFinder->find();
        if ($php === false) {
            throw new RuntimeException('Can\'t find a local PHP executable.');
        }

        $command = \sprintf(
            '%s -S %s:%d -t %s %s',
            $php,
            $host,
            $port,
            $this->getPath().'/'.(string) $this->getBuilder()->getConfig()->get('output.dir'),
            Util::joinFile($this->getPath(), self::TMP_DIR, 'router.php')
        );
        $process = Process::fromShellCommandline($command);

        $buildProcessArguments = [
            $php,
            $_SERVER['argv'][0],
        ];
        $buildProcessArguments[] = 'build';
        if (!empty($this->getConfigFiles())) {
            $buildProcessArguments[] = '--config';
            $buildProcessArguments[] = implode(',', $this->getConfigFiles());
        }
        if ($drafts) {
            $buildProcessArguments[] = '--drafts';
        }
        if ($postprocess === null) {
            $buildProcessArguments[] = '--postprocess';
        }
        if (!empty($postprocess)) {
            $buildProcessArguments[] = '--postprocess';
            $buildProcessArguments[] = $postprocess;
        }
        if ($clearcache) {
            $buildProcessArguments[] = '--clear-cache';
        }
        if ($verbose) {
            $buildProcessArguments[] = '-'.str_repeat('v', $_SERVER['SHELL_VERBOSITY']);
        }
        if (!empty($page)) {
            $buildProcessArguments[] = '--page';
            $buildProcessArguments[] = $page;
        }

        $buildProcess = new Process(array_merge($buildProcessArguments, [$this->getPath()]));

        if ($this->getBuilder()->isDebug()) {
            $output->writeln(\sprintf('<comment>Process: %s</comment>', implode(' ', $buildProcessArguments)));
        }

        $buildProcess->setTty(Process::isTtySupported());
        $buildProcess->setPty(Process::isPtySupported());

        $processOutputCallback = function ($type, $data) use ($output) {
            $output->write($data, false, OutputInterface::OUTPUT_RAW);
        };

        // (re)builds before serve
        $buildProcess->run($processOutputCallback);
        if ($buildProcess->isSuccessful()) {
            $this->fs->dumpFile(Util::joinFile($this->getPath(), self::TMP_DIR, 'changes.flag'), time());
        }
        if ($buildProcess->getExitCode() !== 0) {
            return 1;
        }

        // handles process
        if (!$process->isStarted()) {
            // set resource watcher
            $finder = new Finder();
            $finder->files()
                ->in($this->getPath())
                ->exclude($this->getBuilder()->getConfig()->getOutputPath());
            if (file_exists(Util::joinFile($this->getPath(), '.gitignore'))) {
                $finder->ignoreVCSIgnored(true);
            }
            $hashContent = new Crc32ContentHash();
            $resourceCache = new ResourceCacheMemory();
            $resourceWatcher = new ResourceWatcher($resourceCache, $finder, $hashContent);
            $resourceWatcher->initialize();

            // starts server
            try {
                if (function_exists('\pcntl_signal')) {
                    \pcntl_async_signals(true);
                    \pcntl_signal(SIGINT, [$this, 'tearDownServer']);
                    \pcntl_signal(SIGTERM, [$this, 'tearDownServer']);
                }
                $output->writeln(
                    \sprintf('Starting server (<href=http://%s:%d>%s:%d</>)...', $host, $port, $host, $port)
                );
                $process->start();
                if ($open) {
                    $output->writeln('Opening web browser...');
                    Util\Plateform::openBrowser(\sprintf('http://%s:%s', $host, $port));
                }
                while ($process->isRunning()) {
                    if ($resourceWatcher->findChanges()->hasChanges()) {
                        // re-builds
                        $output->writeln('<comment>Changes detected.</comment>');
                        $output->writeln('');

                        $buildProcess->run($processOutputCallback);
                        if ($buildProcess->isSuccessful()) {
                            $this->fs->dumpFile(Util::joinFile($this->getPath(), self::TMP_DIR, 'changes.flag'), time());
                        }

                        $output->writeln('<info>Server is runnning...</info>');
                    }
                }
            } catch (ProcessFailedException $e) {
                $this->tearDownServer();

                throw new RuntimeException(\sprintf($e->getMessage()));
            }
        }

        return 0;
    }

    /**
     * Prepares server's files.
     *
     * @throws RuntimeException
     */
    private function setUpServer(string $host, string $port): void
    {
        try {
            $root = Util::joinFile(__DIR__, '../../');
            if (Util\Plateform::isPhar()) {
                $root = Util\Plateform::getPharPath().'/';
            }
            // copying router
            $this->fs->copy(
                $root.'/resources/server/router.php',
                Util::joinFile($this->getPath(), self::TMP_DIR, 'router.php'),
                true
            );
            // copying livereload JS
            $this->fs->copy(
                $root.'/resources/server/livereload.js',
                Util::joinFile($this->getPath(), self::TMP_DIR, 'livereload.js'),
                true
            );
            // copying baseurl text file
            $this->fs->dumpFile(
                Util::joinFile($this->getPath(), self::TMP_DIR, 'baseurl'),
                \sprintf(
                    '%s;%s',
                    (string) $this->getBuilder()->getConfig()->get('baseurl'),
                    \sprintf('http://%s:%s/', $host, $port)
                )
            );
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('An error occurred while copying server\'s files to "%s"', $e->getPath()));
        }
        if (!is_file(Util::joinFile($this->getPath(), self::TMP_DIR, 'router.php'))) {
            throw new RuntimeException(\sprintf('Router not found: "%s"', Util::joinFile(self::TMP_DIR, 'router.php')));
        }
    }

    /**
     * Removes temporary directory.
     *
     * @throws RuntimeException
     */
    private function tearDownServer(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<comment>Server stopped.</comment>');

        try {
            $this->fs->remove(Util::joinFile($this->getPath(), self::TMP_DIR));
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
