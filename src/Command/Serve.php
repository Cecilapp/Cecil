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
use Symfony\Component\Console\Command\Command;
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
use Yosymfony\ResourceWatcher\ResourceWatcherResult;

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

    /** @var boolean */
    protected $incrementalEnabled = false;

    /** @var string PHP executable path used to spawn build processes. */
    protected $php;

    /** @var float Build process timeout, in seconds. */
    protected $processTimeout;

    /**
     * Base options used to build the `build` process arguments.
     * @var array<string, mixed>
     */
    protected $buildProcessBaseOptions = [];

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
                new InputOption('incremental', 'i', InputOption::VALUE_NONE, 'Enable incremental builds (rebuild only changed pages)'),
                new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                new InputOption('optimize', null, InputOption::VALUE_NEGATABLE, 'Enable (or disable --no-optimize) optimization of generated files'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to extra config files (comma-separated)'),
                new InputOption('clear-cache', null, InputOption::VALUE_OPTIONAL, 'Clear cache before build (optional cache key as regular expression)', false),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Build a specific page'),
                new InputOption('no-ignore-vcs', null, InputOption::VALUE_NONE, 'Changes watcher must not ignore VCS directories'),
                new InputOption('metrics', 'm', InputOption::VALUE_NONE, 'Show build metrics (duration and memory) of each step'),
                new InputOption('timeout', null, InputOption::VALUE_REQUIRED, 'Sets the process timeout (max. runtime) in seconds', 7200), // default is 2 hours
                new InputOption('notify', null, InputOption::VALUE_NONE, 'Send desktop notification on server start'),
                new InputOption('background', 'b', InputOption::VALUE_NONE, 'Run the server in the background'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command starts the live-reloading-built-in web server.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>
  <info>%command.full_name% --open</>
  <info>%command.full_name% --drafts</>
  <info>%command.full_name% --no-watch</>

To speed up local development you can enable <comment>incremental builds</comment> with the <info>--incremental</info> option.
When only content pages change, Cecil rebuilds <comment>just those pages</comment> instead of the whole website;
any other change (layout, data, config, theme, static or asset file) triggers a full rebuild:

  <info>%command.full_name% --incremental</>

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

  <info>%command.full_name% --notify</>

To run the server in the <comment>background</comment>, run:

  <info>%command.full_name% --background</>
  <info>%command.full_name% -b</>

Then stop it with:

  <info>%bin_name% serve:stop</>
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
        $notify = $input->getOption('notify');
        $noansi = $input->getOption('no-ansi');
        $background = $input->getOption('background');
        $incremental = $input->getOption('incremental');

        $this->watcherEnabled = $background ? false : $input->getOption('watch');
        // incremental builds require the changes watcher to be active
        $this->incrementalEnabled = $this->watcherEnabled ? (bool) $incremental : false;

        // checks if PHP executable is available
        $phpFinder = new PhpExecutableFinder();
        $php = $phpFinder->find();
        if ($php === false) {
            throw new RuntimeException('Unable to find a local PHP executable.');
        }
        // setup server
        $this->setUpServer();
        $cmd = [
            $php,
            '-S',
            $host . ':' . (int) $port,
            '-t',
            Util::joinFile($this->getPath(), self::SERVE_OUTPUT),
            Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'),
        ];
        $command = implode(' ', $cmd);
        $process = new Process($cmd);

        // setup build process
        $this->php = $php;
        $this->processTimeout = (float) $timeout;
        $this->buildProcessBaseOptions = [
            'drafts' => $drafts,
            'optimize' => $optimize,
            'clearcache' => $clearcache,
            'verbose' => $verbose,
            'page' => $page,
            'metrics' => $metrics,
            'noansi' => $noansi,
            'host' => $host,
            'port' => $port,
        ];
        $buildProcessArguments = $this->createBuildProcessArguments($php, $this->buildProcessBaseOptions);
        $buildProcess = $this->createBuildProcess();
        $buildOutputEndsWithLineFeed = true;
        $processOutputCallback = function ($type, $buffer) use ($output, &$buildOutputEndsWithLineFeed) {
            $buildOutputEndsWithLineFeed = str_ends_with($buffer, "\n");
            // Progress bars use carriage returns; normalize them in non-decorated output.
            if (!$output->isDecorated()) {
                $buffer = str_replace("\r", "\n", $buffer);
            }
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        };
        $flushBuildOutput = function () use ($output, &$buildOutputEndsWithLineFeed): void {
            if (!$buildOutputEndsWithLineFeed) {
                $output->writeln('');
                $buildOutputEndsWithLineFeed = true;
            }
        };

        // builds before serve
        if (!$this->runInitialBuild($buildProcess, $processOutputCallback, $flushBuildOutput, $output, $buildProcessArguments)) {
            $this->tearDownServer();

            return Command::FAILURE;
        }

        // background mode: start server as a detached process and exit
        if ($background) {
            return $this->startServerInBackground($php, (string) $host, (int) $port, $output, (bool) $notify, (bool) $open);
        }

        return $this->runForegroundServer(
            $process,
            (bool) $noignorevcs,
            (string) $host,
            (int) $port,
            $command,
            $output,
            [
                'notify' => (bool) $notify,
                'open' => (bool) $open,
            ],
            $buildProcess,
            $processOutputCallback,
            $flushBuildOutput
        );
    }

    /**
     * Creates a configured `build` process, optionally restricted to a single page.
     */
    private function createBuildProcess(?string $page = null): Process
    {
        $options = $this->buildProcessBaseOptions;
        if ($page !== null) {
            $options['page'] = $page;
        }
        $process = new Process(
            $this->createBuildProcessArguments($this->php, $options),
            null,
            ['BOX_REQUIREMENT_CHECKER' => '0'] // prevents double check (build then serve)
        );
        $process->setTty(Process::isTtySupported());
        $process->setPty(Process::isPtySupported());
        if ($this->processTimeout !== null) {
            $process->setTimeout($this->processTimeout);
        }

        return $process;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    private function createBuildProcessArguments(string $php, array $options): array
    {
        $buildProcessArguments = [
            $php,
            $_SERVER['argv'][0],
            'build',
            $this->getPath(),
        ];

        if (!empty($this->getConfigFiles())) {
            $buildProcessArguments[] = '--config';
            $buildProcessArguments[] = implode(',', $this->getConfigFiles());
        }
        if ($options['drafts']) {
            $buildProcessArguments[] = '--drafts';
        }
        if ($options['optimize'] === true) {
            $buildProcessArguments[] = '--optimize';
        }
        if ($options['optimize'] === false) {
            $buildProcessArguments[] = '--no-optimize';
        }
        if ($options['clearcache'] === null) {
            $buildProcessArguments[] = '--clear-cache';
        }
        if (!empty($options['clearcache'])) {
            $buildProcessArguments[] = '--clear-cache';
            $buildProcessArguments[] = (string) $options['clearcache'];
        }
        if ($options['verbose']) {
            $buildProcessArguments[] = '-' . str_repeat('v', (int) $_SERVER['SHELL_VERBOSITY']);
        }
        if (!empty($options['page'])) {
            $buildProcessArguments[] = '--page';
            $buildProcessArguments[] = (string) $options['page'];
        }
        if ($options['metrics']) {
            $buildProcessArguments[] = '--metrics';
        }
        if ($options['noansi']) {
            $buildProcessArguments[] = '--no-ansi';
        }
        $buildProcessArguments[] = '--baseurl';
        $buildProcessArguments[] = \sprintf('http://%s:%s/', (string) $options['host'], (string) $options['port']);
        $buildProcessArguments[] = '--output';
        $buildProcessArguments[] = self::SERVE_OUTPUT;

        return $buildProcessArguments;
    }

    /**
     * @param array<int, string> $buildProcessArguments
     */
    private function runInitialBuild(
        Process $buildProcess,
        callable $processOutputCallback,
        callable $flushBuildOutput,
        OutputInterface $output,
        array $buildProcessArguments
    ): bool {
        $output->writeln(\sprintf('<comment>Build process: %s</comment>', implode(' ', $buildProcessArguments)), OutputInterface::VERBOSITY_DEBUG);
        $buildProcess->run($processOutputCallback);
        $flushBuildOutput();

        if ($buildProcess->isSuccessful()) {
            $this->buildSuccessActions($output);
        }

        return $buildProcess->getExitCode() === 0;
    }

    private function startServerInBackground(
        string $php,
        string $host,
        int $port,
        OutputInterface $output,
        bool $notify,
        bool $open
    ): int {
        $pid = $this->startDetachedServerProcess($php, $host, $port);

        if ($pid <= 0) {
            $this->tearDownServer();
            throw new RuntimeException('Unable to start the server in the background.');
        }

        Util\File::getFS()->dumpFile(Util::joinFile($this->getPath(), self::PID_FILE), (string) $pid);
        $output->writeln(\sprintf('<info>Starting server in the background (PID: %d)</info>', $pid));
        $output->writeln(\sprintf('Server running at <href=http://%s:%d>http://%s:%d</>', $host, $port, $host, $port));
        $output->writeln(\sprintf('To stop the server, run: <info>%s serve:stop</info>', $this->binName()));

        if ($notify) {
            $this->notification('Starting server in background 🚀', \sprintf('http://%s:%s', $host, $port));
        }
        if ($open) {
            $output->writeln('Opening web browser...');
            Util\Platform::openBrowser(\sprintf('http://%s:%s', $host, $port));
        }

        return Command::SUCCESS;
    }

    private function startDetachedServerProcess(string $php, string $host, int $port): int
    {
        $pidOutput = [];

        if (Util\Platform::isWindows()) {
            // Use PowerShell Start-Process to launch detached hidden process
            $phpWin = str_replace('/', '\\', $php);
            $serverArgs = \sprintf(
                '-S %s:%d -t "%s" "%s"',
                $host,
                $port,
                str_replace('/', '\\', Util::joinFile($this->getPath(), self::SERVE_OUTPUT)),
                str_replace('/', '\\', Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'))
            );
            $psCommand = \sprintf(
                'powershell -NoProfile -Command "(Start-Process -PassThru -WindowStyle Hidden -FilePath \'%s\' -ArgumentList \'%s\').Id"',
                $phpWin,
                str_replace("'", "''", $serverArgs)
            );
            exec($psCommand, $pidOutput);
        } else {
            exec(\sprintf(
                'nohup %s -S %s -t %s %s & echo $!',
                escapeshellarg($php),
                escapeshellarg($host . ':' . $port),
                escapeshellarg(Util::joinFile($this->getPath(), self::SERVE_OUTPUT)),
                escapeshellarg(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'))
            ), $pidOutput);
        }

        return (int) ($pidOutput[0] ?? 0);
    }

    private function runForegroundServer(
        Process $process,
        bool $noignorevcs,
        string $host,
        int $port,
        string $command,
        OutputInterface $output,
        array $options,
        Process $buildProcess,
        callable $processOutputCallback,
        callable $flushBuildOutput
    ): int {
        $notify = (bool) ($options['notify'] ?? false);
        $open = (bool) ($options['open'] ?? false);
        $resourceWatcher = null;

        if ($process->isStarted()) {
            return Command::SUCCESS;
        }

        $messageSuffix = '';
        if ($this->watcherEnabled) {
            $resourceWatcher = $this->setupWatcher($noignorevcs);
            $resourceWatcher->initialize();
            $messageSuffix = ' with changes watcher';
        }

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

            if ($notify) {
                $this->notification('Starting server 🚀', \sprintf('http://%s:%s', $host, $port));
            }
            if ($open) {
                $output->writeln('Opening web browser...');
                Util\Platform::openBrowser(\sprintf('http://%s:%s', $host, $port));
            }

            while ($process->isRunning()) {
                sleep(1); // wait for server is ready
                if (!fsockopen($host, $port)) {
                    $output->writeln('<info>Server is not ready</info>');

                    return Command::FAILURE;
                }
                if ($this->watcherEnabled && $resourceWatcher instanceof ResourceWatcher) {
                    $watcher = $resourceWatcher->findChanges();
                    if ($watcher->hasChanges()) {
                        $output->writeln('<comment>Changes detected</comment>');
                        if ($notify) {
                            $this->notification('Changes detected, building website...');
                        }
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

                        if ($this->incrementalEnabled) {
                            $this->runIncrementalBuild($watcher, $output, $processOutputCallback, $flushBuildOutput, $buildProcess);
                        } else {
                            $buildProcess->run($processOutputCallback);
                            $flushBuildOutput();
                            if ($buildProcess->isSuccessful()) {
                                $this->buildSuccessActions($output);
                            }
                        }
                        $output->writeln('<info>Server is running...</info>');
                        if ($notify) {
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

        return Command::SUCCESS;
    }

    /**
     * Runs an incremental build based on the watcher result.
     *
     * If every change is an isolated content page (created or updated), Cecil rebuilds
     * only those pages. Any other change (deletion, layout, data, config, theme, static
     * or asset file) triggers a full rebuild.
     */
    private function runIncrementalBuild(
        ResourceWatcherResult $watcher,
        OutputInterface $output,
        callable $processOutputCallback,
        callable $flushBuildOutput,
        Process $fullBuildProcess
    ): void {
        $pages = $this->resolveIncrementalPages($watcher);

        // null means a full rebuild is required
        if ($pages === null) {
            $output->writeln('<comment>Incremental: full rebuild required</comment>');
            $fullBuildProcess->run($processOutputCallback);
            $flushBuildOutput();
            if ($fullBuildProcess->isSuccessful()) {
                $this->buildSuccessActions($output);
            }

            return;
        }

        $allSuccessful = true;
        foreach ($pages as $page) {
            $output->writeln(\sprintf('<comment>Incremental: building page "%s"</comment>', $page));
            $process = $this->createBuildProcess($page);
            $process->run($processOutputCallback);
            $flushBuildOutput();
            if (!$process->isSuccessful()) {
                $allSuccessful = false;
            }
        }
        if ($allSuccessful) {
            $this->buildSuccessActions($output);
        }
    }

    /**
     * Resolves the list of content pages to rebuild from the watcher result.
     *
     * Returns an array of page paths (relative to the pages directory) when an incremental
     * rebuild is possible, or `null` when a full rebuild is required.
     *
     * @return array<int, string>|null
     */
    private function resolveIncrementalPages(ResourceWatcherResult $watcher): ?array
    {
        // a deletion may impact lists, sections, taxonomies, etc.: full rebuild
        if (\count($watcher->getDeletedFiles()) > 0) {
            return null;
        }

        $changedFiles = array_merge($watcher->getNewFiles(), $watcher->getUpdatedFiles());
        if (\count($changedFiles) === 0) {
            return null;
        }

        $config = $this->getBuilder()->getConfig();
        $pagesPath = $this->normalizePath($config->getPagesPath());
        $extensions = array_map('strtolower', (array) $config->get('pages.ext'));

        $pages = [];
        foreach ($changedFiles as $file) {
            $normalized = $this->normalizePath($file);
            // file must live inside the pages directory
            if (strncmp($normalized, $pagesPath . '/', \strlen($pagesPath) + 1) !== 0) {
                return null;
            }
            // file extension must be a valid page extension
            $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
            if (!\in_array($extension, $extensions, true)) {
                return null;
            }
            $relative = substr($normalized, \strlen($pagesPath) + 1);
            $pages[$relative] = $relative;
        }

        return array_values($pages);
    }

    /**
     * Normalizes a filesystem path to use forward slashes without a trailing slash.
     */
    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
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
            // creates temporary server directory
            Util\File::getFS()->mkdir(Util::joinFile($this->getPath(), Builder::TMP_DIR));
            // copying router file to temporary server directory
            Util\File::getFS()->copy(
                $this->rootPath . 'resources/server/router.php',
                Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'),
                true
            );
            Util\File::getFS()->chmod(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'), 0777 & ~umask());
            // copying livereload JS (for watcher) to temporary server directory
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
                Util\File::getFS()->chmod($livereloadJs, 0777 & ~umask());
            }
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('An error occurred while copying server\'s files: "%s".', $e->getMessage()));
        }
        if (!is_file(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'router.php'))) {
            throw new RuntimeException(\sprintf('Router not found: "%s".', Util::joinFile(Builder::TMP_DIR, 'router.php')));
        }
    }

    /**
     * Removes server's files.
     *
     * @throws RuntimeException
     */
    public function tearDownServer(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<info>Server stopped</info>');

        try {
            Util\File::getFS()->remove(Util::joinFile($this->getPath(), self::SERVE_OUTPUT));
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
