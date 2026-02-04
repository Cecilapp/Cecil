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
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
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
 * Serve command.
 *
 * This command starts the built-in web server with live reloading capabilities.
 * It allows users to serve their website locally and automatically rebuild it when changes are detected.
 * It also supports opening the web browser automatically and includes options for drafts, optimization, and more.
 */
class Serve extends AbstractCommand
{
    /** @var boolean */
    protected $watcherEnabled;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('serve')
            ->setDescription('Starts the built-in server')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open web browser automatically'),
                new InputOption('host', null, InputOption::VALUE_REQUIRED, 'Server host', 'localhost'),
                new InputOption('port', null, InputOption::VALUE_REQUIRED, 'Server port', '8000'),
                new InputOption('watch', 'w', InputOption::VALUE_NEGATABLE, 'Enable (or disable --no-watch) changes watcher (enabled by default)', true),
                new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                new InputOption('optimize', null, InputOption::VALUE_NEGATABLE, 'Enable (or disable --no-optimize) optimization of generated files'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to extra config files (comma-separated)'),
                new InputOption('clear-cache', null, InputOption::VALUE_OPTIONAL, 'Clear cache before build (optional cache key as regular expression)', false),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Build a specific page'),
                new InputOption('no-ignore-vcs', null, InputOption::VALUE_NONE, 'Changes watcher must not ignore VCS directories'),
                new InputOption('metrics', 'm', InputOption::VALUE_NONE, 'Show build metrics (duration and memory) of each step'),
                new InputOption('timeout', null, InputOption::VALUE_REQUIRED, 'Sets the process timeout (max. runtime) in seconds', 7200), // default is 2 hours
                new InputOption('notif', null, InputOption::VALUE_NONE, 'Send desktop notification on server start'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command starts the live-reloading-built-in web server.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>
  <info>%command.full_name% --open</>
  <info>%command.full_name% --drafts</>
  <info>%command.full_name% --no-watch</>

You can use a custom host and port by using the <info>--host</info> and <info>--port</info> options:

  <info>%command.full_name% --host=127.0.0.1 --port=8080</>

To build the website with an extra configuration file, you can use the <info>--config</info> option.
This is useful during local development to <comment>override some settings</comment> without modifying the main configuration:

  <info>%command.full_name% --config=config/dev.yml</>

To start the server with changes watcher <comment>not ignoring VCS</comment> directories, run:

  <info>%command.full_name% --no-ignore-vcs</>

To define the process <comment>timeout</comment> (in seconds), run:

  <info>%command.full_name% --timeout=7200</>

Send a desktop <comment>notification</comment> on server start, run:

  <info>%command.full_name% --notif</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $open = $input->getOption('open');
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $drafts = $input->getOption('drafts');
        $optimize = $input->getOption('optimize');
        $clearcache = $input->getOption('clear-cache');
        $page = $input->getOption('page');
        $noignorevcs = $input->getOption('no-ignore-vcs');
        $metrics = $input->getOption('metrics');
        $timeout = $input->getOption('timeout');
        $verbose = $input->getOption('verbose');
        $notif = $input->getOption('notif');

        $resourceWatcher = null;
        $this->watcherEnabled = $input->getOption('watch');

        // checks if PHP executable is available
        $phpFinder = new PhpExecutableFinder();
        $php = $phpFinder->find();
        if ($php === false) {
            throw new RuntimeException('Unable to find a local PHP executable.');
        }

        // setup server
        $this->setUpServer();
        $command = \sprintf(
            '"%s" -S %s:%d -t "%s" "%s"',
            $php,
            $host,
            $port,
            Util::joinFile($this->getPath(), self::SERVE_OUTPUT),
            Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php')
        );
        $process = Process::fromShellCommandline($command);

        // setup build process
        $buildProcessArguments = [
            $php,
            $_SERVER['argv'][0],
        ];
        $buildProcessArguments[] = 'build';
        $buildProcessArguments[] = $this->getPath();
        if (!empty($this->getConfigFiles())) {
            $buildProcessArguments[] = '--config';
            $buildProcessArguments[] = implode(',', $this->getConfigFiles());
        }
        if ($drafts) {
            $buildProcessArguments[] = '--drafts';
        }
        if ($optimize === true) {
            $buildProcessArguments[] = '--optimize';
        }
        if ($optimize === false) {
            $buildProcessArguments[] = '--no-optimize';
        }
        if ($clearcache === null) {
            $buildProcessArguments[] = '--clear-cache';
        }
        if (!empty($clearcache)) {
            $buildProcessArguments[] = '--clear-cache';
            $buildProcessArguments[] = $clearcache;
        }
        if ($verbose) {
            $buildProcessArguments[] = '-' . str_repeat('v', $_SERVER['SHELL_VERBOSITY']);
        }
        if (!empty($page)) {
            $buildProcessArguments[] = '--page';
            $buildProcessArguments[] = $page;
        }
        if ($metrics) {
            $buildProcessArguments[] = '--metrics';
        }
        $buildProcessArguments[] = '--baseurl';
        $buildProcessArguments[] = "http://$host:$port/";
        $buildProcessArguments[] = '--output';
        $buildProcessArguments[] = self::SERVE_OUTPUT;
        $buildProcess = new Process(
            $buildProcessArguments,
            null,
            ['BOX_REQUIREMENT_CHECKER' => '0'] // prevents double check (build then serve)
        );
        $buildProcess->setTty(Process::isTtySupported());
        $buildProcess->setPty(Process::isPtySupported());
        $buildProcess->setTimeout((float) $timeout);
        $processOutputCallback = function ($type, $buffer) use ($output) {
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        };

        // builds before serve
        $output->writeln(\sprintf('<comment>Build process: %s</comment>', implode(' ', $buildProcessArguments)), OutputInterface::VERBOSITY_DEBUG);
        $buildProcess->run($processOutputCallback);
        if ($buildProcess->isSuccessful()) {
            $this->buildSuccessActions($output);
        }
        if ($buildProcess->getExitCode() !== 0) {
            $this->tearDownServer();

            return 1;
        }

        // handles serve process
        if (!$process->isStarted()) {
            $messageSuffix = '';
            // setup resource watcher
            if ($this->watcherEnabled) {
                $resourceWatcher = $this->setupWatcher($noignorevcs);
                $resourceWatcher->initialize();
                $messageSuffix = ' with changes watcher';
            }
            // starts server
            try {
                if (\function_exists('\pcntl_signal')) {
                    pcntl_async_signals(true);
                    pcntl_signal(SIGINT, [$this, 'tearDownServer']);
                    pcntl_signal(SIGTERM, [$this, 'tearDownServer']);
                }
                $output->writeln(\sprintf('<comment>Server process: %s</comment>', $command), OutputInterface::VERBOSITY_DEBUG);
                $output->writeln(\sprintf('Starting server%s (<href=http://%s:%d>http://%s:%d</>)', $messageSuffix, $host, $port, $host, $port));
                $process->start(function ($type, $buffer) {
                    if ($type === Process::ERR) {
                        error_log($buffer, 3, Util::joinFile($this->getPath(), Builder::TMP_DIR, 'errors.log'));
                    }
                });
                // notification
                if ($notif) {
                    $this->notification('Starting server 🚀', \sprintf('http://%s:%s', $host, $port));
                }
                // open web browser
                if ($open) {
                    $output->writeln('Opening web browser...');
                    Util\Platform::openBrowser(\sprintf('http://%s:%s', $host, $port));
                }
                while ($process->isRunning()) {
                    sleep(1); // wait for server is ready
                    if (!fsockopen($host, (int) $port)) {
                        $output->writeln('<info>Server is not ready</info>');

                        return 1;
                    }
                    if ($this->watcherEnabled && $resourceWatcher instanceof ResourceWatcher) {
                        $watcher = $resourceWatcher->findChanges();
                        if ($watcher->hasChanges()) {
                            $output->writeln('<comment>Changes detected</comment>');
                            // notification
                            if ($notif) {
                                $this->notification('Changes detected, building website...');
                            }
                            // prints deleted/new/updated files in debug mode
                            if (\count($watcher->getDeletedFiles()) > 0) {
                                $output->writeln('<comment>Deleted files:</comment>', OutputInterface::VERBOSITY_DEBUG);
                                foreach ($watcher->getDeletedFiles() as $file) {
                                    $output->writeln("<comment>- $file</comment>", OutputInterface::VERBOSITY_DEBUG);
                                }
                            }
                            if (\count($watcher->getNewFiles()) > 0) {
                                $output->writeln('<comment>New files:</comment>', OutputInterface::VERBOSITY_DEBUG);
                                foreach ($watcher->getNewFiles() as $file) {
                                    $output->writeln("<comment>- $file</comment>", OutputInterface::VERBOSITY_DEBUG);
                                }
                            }
                            if (\count($watcher->getUpdatedFiles()) > 0) {
                                $output->writeln('<comment>Updated files:</comment>', OutputInterface::VERBOSITY_DEBUG);
                                foreach ($watcher->getUpdatedFiles() as $file) {
                                    $output->writeln("<comment>- $file</comment>", OutputInterface::VERBOSITY_DEBUG);
                                }
                            }
                            $output->writeln('');
                            // re-builds
                            $buildProcess->run($processOutputCallback);
                            if ($buildProcess->isSuccessful()) {
                                $this->buildSuccessActions($output);
                            }
                            $output->writeln('<info>Server is running...</info>');
                            // notification
                            if ($notif) {
                                $this->notification('Server is running...');
                            }
                        }
                    }
                }
                if ($process->getExitCode() > 0) {
                    $output->writeln(\sprintf('<comment>%s</comment>', trim($process->getErrorOutput())));
                }
            } catch (ProcessFailedException $e) {
                $this->tearDownServer();

                throw new RuntimeException(\sprintf($e->getMessage()));
            }
        }

        return 0;
    }

    /**
     * Build success actions.
     */
    private function buildSuccessActions(OutputInterface $output): void
    {
        // writes `changes.flag` file
        if ($this->watcherEnabled) {
            Util\File::getFS()->dumpFile(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'changes.flag'), time());
        }
        // writes `headers.ini` file
        $headers = $this->getBuilder()->getConfig()->get('server.headers');
        if (is_iterable($headers)) {
            $output->writeln('Writing headers file...');
            Util\File::getFS()->remove(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'headers.ini'));
            foreach ($headers as $entry) {
                Util\File::getFS()->appendToFile(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'headers.ini'), "[{$entry['path']}]\n");
                foreach ($entry['headers'] ?? [] as $header) {
                    Util\File::getFS()->appendToFile(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'headers.ini'), "{$header['key']} = \"{$header['value']}\"\n");
                }
            }
        }
    }

    /**
     * Sets up the watcher.
     */
    private function setupWatcher(bool $noignorevcs = false): ResourceWatcher
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->getPath())
            ->exclude((string) $this->getBuilder()->getConfig()->get('output.dir'));
        if (file_exists(Util::joinFile($this->getPath(), '.gitignore')) && $noignorevcs === false) {
            $finder->ignoreVCSIgnored(true);
        }
        return new ResourceWatcher(new ResourceCacheMemory(), $finder, new Crc32ContentHash());
    }

    /**
     * Prepares server's files.
     *
     * @throws RuntimeException
     */
    private function setUpServer(): void
    {
        try {
            // copying router
            Util\File::getFS()->copy(
                $this->rootPath . 'resources/server/router.php',
                Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'),
                true
            );
            // copying livereload JS for watcher
            $livereloadJs = Util::joinFile($this->getPath(), Builder::TMP_DIR, 'livereload.js');
            if (is_file($livereloadJs)) {
                Util\File::getFS()->remove($livereloadJs);
            }
            if ($this->watcherEnabled) {
                Util\File::getFS()->copy(
                    $this->rootPath . 'resources/server/livereload.js',
                    $livereloadJs,
                    true
                );
            }
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('An error occurred while copying server\'s files to "%s".', $e->getPath()));
        }
        if (!is_file(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'))) {
            throw new RuntimeException(\sprintf('Router not found: "%s".', Util::joinFile(Builder::TMP_DIR, 'router.php')));
        }
    }

    /**
     * Removes temporary directory.
     *
     * @throws RuntimeException
     */
    public function tearDownServer(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<info>Server stopped</info>');

        try {
            Util\File::getFS()->remove(Util::joinFile($this->getPath(), Builder::TMP_DIR));
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
