<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Util\Plateform;
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

class CommandServe extends Command
{
    /**
     * @var string
     */
    public static $tmpDir = '.cecil';
    /**
     * @var string
     */
    protected $drafts;
    /**
     * @var bool
     */
    protected $open;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string
     */
    protected $port;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('serve')
            ->setAliases(['s'])
            ->setDescription('Start the built-in server')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'If specified, use the given path as working directory'),
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open browser automatically'),
                    new InputOption('host', null, InputOption::VALUE_OPTIONAL, 'Server host'),
                    new InputOption('port', null, InputOption::VALUE_OPTIONAL, 'Server port'),
                ])
            )
            ->setHelp('Start the live-reloading-built-in web server.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->drafts = $input->getOption('drafts');
        $this->open = $input->getOption('open');
        $this->host = $input->getOption('host') ?? 'localhost';
        $this->port = $input->getOption('port') ?? '8000';

        $this->setUpServer($output);
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            $this->host,
            $this->port,
            $this->getPath().'/'.$this->getBuilder($output)->getConfig()->get('output.dir'),
            sprintf('%s/%s/router.php', $this->getPath(), self::$tmpDir)
        );
        $process = new Process($command);

        // (re)build before serve
        $buildCommand = $this->getApplication()->find('build');
        $buildInput = new ArrayInput([
            'command'  => 'build',
            'path'     => $this->getPath(),
            '--drafts' => $this->drafts,
        ]);
        $returnCode = $buildCommand->run($buildInput, $output);

        // handle process
        if (!$process->isStarted()) {
            // write changes cache
            $finder = new Finder();
            $finder->files()
                ->name('*.md')
                ->name('*.twig')
                ->name('*.yml')
                ->name('*.css')
                ->name('*.scss')
                ->name('*.js')
                ->in($this->getPath())
                ->exclude($this->getBuilder($output)->getConfig()->get('output.dir'));
            if (!$this->nowatcher) {
                $hashContent = new Crc32ContentHash();
                $resourceCache = new ResourceCacheMemory();
                $resourceWatcher = new ResourceWatcher($resourceCache, $finder, $hashContent);
                $resourceWatcher->initialize();
            }
            // start server
            try {
                $output->writeln(sprintf('<info>Starting server (http://%s:%d)...</info>', $this->host, $this->port));
                $process->start();
                if ($this->open) {
                    Plateform::openBrowser(sprintf('http://%s:%s', $this->host, $this->port));
                }
                while ($process->isRunning()) {
                    if (!$this->nowatcher) {
                        $result = $resourceWatcher->findChanges();
                        if ($result->hasChanges()) {
                            // re-build
                            $output->writeln('<comment>Changes detected.</comment>');
                            $returnCode = $buildCommand->run($buildInput, $output);
                        }
                        usleep(1000000); // wait 1s
                    }
                }
            } catch (ProcessFailedException $e) {
                $this->tearDownServer();

                throw new \Exception(sprintf($e->getMessage()));
            }
        }

        return 0;
    }

    private function setUpServer($output)
    {
        try {
            $root = __DIR__.'/../../';
            if (Plateform::isPhar()) {
                $root = Plateform::getPharPath().'/';
            }
            // copy router
            $this->fs->copy(
                $root.'res/server/router.php',
                $this->getPath().'/'.self::$tmpDir.'/router.php',
                true
            );
            // copy livereload JS
            if (!$this->nowatcher) {
                $this->fs->copy(
                    $root.'res/server/livereload.js',
                    $this->getPath().'/'.self::$tmpDir.'/livereload.js',
                    true
                );
            }
            // copy baseurl text file
            $this->fs->dumpFile(
                $this->getPath().'/'.self::$tmpDir.'/baseurl',
                sprintf(
                    '%s;%s',
                    $this->getBuilder($output)->getConfig()->get('baseurl'),
                    sprintf('http://%s:%s/', $this->host, $this->port)
                )
            );
        } catch (IOExceptionInterface $e) {
            throw new \Exception(sprintf('An error occurred while copying file at "%s"', $e->getPath()));
        }
        if (!is_file(sprintf('%s/%s/router.php', $this->getPath(), self::$tmpDir))) {
            throw new \Exception(sprintf('Router not found: "./%s/router.php"', self::$tmpDir));
        }
    }

    public function tearDownServer()
    {
        try {
            $this->fs->remove($this->getPath().'/'.self::$tmpDir);
        } catch (IOExceptionInterface $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }
}
