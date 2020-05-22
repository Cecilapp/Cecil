<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Util;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
                    new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Specific configuration file'),
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open browser automatically'),
                    new InputOption('host', null, InputOption::VALUE_REQUIRED, 'Server host'),
                    new InputOption('port', null, InputOption::VALUE_REQUIRED, 'Server port'),
                    new InputOption(
                        'postprocess',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Post-process output (disable with "no")',
                        false
                    ),
                    new InputOption('clear-cache', null, InputOption::VALUE_NONE, 'Clear cache after build'),
                ])
            )
            ->setHelp('Starts the live-reloading-built-in web server');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drafts = $input->getOption('drafts');
        $open = $input->getOption('open');
        $host = $input->getOption('host') ?? 'localhost';
        $port = $input->getOption('port') ?? '8000';
        $postprocess = $input->getOption('postprocess');

        $this->setUpServer($host, $port);
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            $host,
            $port,
            $this->getPath().'/'.(string) $this->getBuilder()->getConfig()->get('output.dir'),
            Util::joinFile($this->getPath(), self::TMP_DIR, 'router.php')
        );
        $process = Process::fromShellCommandline($command);

        // (re)builds before serve
        $buildCommand = $this->getApplication()->find('build');
        $buildImputArray = [
            'command'       => 'build',
            'path'          => $this->getPath(),
            '--drafts'      => $drafts,
            '--postprocess' => $postprocess,
        ];
        if ($this->getConfigFile() !== null) {
            $buildImputArray = array_merge($buildImputArray, [
                '--config' => $this->getConfigFile(),
            ]);
        }
        $buildInput = new ArrayInput($buildImputArray);
        if ($buildCommand->run($buildInput, $this->output) != 0) {
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
                    sprintf('Starting server (<href=http://%s:%d>%s:%d</>)...', $host, $port, $host, $port)
                );
                $process->start();
                if ($open) {
                    $output->writeln('Opening web browser...');
                    Util\Plateform::openBrowser(sprintf('http://%s:%s', $host, $port));
                }
                while ($process->isRunning()) {
                    $result = $resourceWatcher->findChanges();
                    if ($result->hasChanges()) {
                        // re-builds
                        $output->writeln('<comment>Changes detected.</comment>');
                        $output->writeln('');
                        $buildCommand->run($buildInput, $output);
                        $output->writeln('<info>Server is runnning...</info>');
                    }
                    usleep(1000000); // waits 1s
                }
            } catch (ProcessFailedException $e) {
                $this->tearDownServer();

                throw new \Exception(sprintf($e->getMessage()));
            }
        }

        return 0;
    }

    /**
     * Prepares server's files.
     *
     * @param string $host
     * @param string $port
     *
     * @throws \Exception
     *
     * @return void
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
                $root.'/res/server/router.php',
                Util::joinFile($this->getPath(), self::TMP_DIR, 'router.php'),
                true
            );
            // copying livereload JS
            $this->fs->copy(
                $root.'/res/server/livereload.js',
                Util::joinFile($this->getPath(), self::TMP_DIR, 'livereload.js'),
                true
            );
            // copying baseurl text file
            $this->fs->dumpFile(
                Util::joinFile($this->getPath(), self::TMP_DIR, 'baseurl'),
                sprintf(
                    '%s;%s',
                    (string) $this->getBuilder()->getConfig()->get('baseurl'),
                    sprintf('http://%s:%s/', $host, $port)
                )
            );
        } catch (IOExceptionInterface $e) {
            throw new \Exception(sprintf('An error occurred while copying server\'s files to "%s"', $e->getPath()));
        }
        if (!is_file(Util::joinFile($this->getPath(), self::TMP_DIR, 'router.php'))) {
            throw new \Exception(sprintf('Router not found: "%s"', Util::joinFile(self::TMP_DIR, 'router.php')));
        }
    }

    /**
     * Removes temporary directory.
     *
     * @throws \Exception
     *
     * @return void
     */
    private function tearDownServer(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<comment>Server stopped.</comment>');

        try {
            $this->fs->remove(Util::joinFile($this->getPath(), self::TMP_DIR));
        } catch (IOExceptionInterface $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
