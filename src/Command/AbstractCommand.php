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

use Cecil\Builder;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\ConsoleLogger;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class AbstractCommand extends Command
{
    const CONFIG_FILE = 'config.yml';
    const TMP_DIR = '.cecil';

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var SymfonyStyle */
    protected $io;

    /** @var Filesystem */
    protected $fs;

    /** @var string */
    protected $path;

    /** @var array */
    protected $configFiles;

    /** @var Builder */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $this->fs = new Filesystem();

        if (!in_array($this->getName(), ['self-update'])) {
            // working directory
            $this->path = getcwd();
            if ($input->getArgument('path') !== null) {
                $this->path = (string) $input->getArgument('path');
            }
            if (realpath($this->getPath()) === false) {
                $this->fs->mkdir($this->getPath());
            }
            $this->path = realpath($this->getPath());
            // config file(s)
            if (!in_array($this->getName(), ['new:site'])) {
                // default
                $this->configFiles[self::CONFIG_FILE] = realpath(Util::joinFile($this->getPath(), self::CONFIG_FILE));
                // from --config=<file>
                if ($input->hasOption('config') && $input->getOption('config') !== null) {
                    foreach (explode(',', (string) $input->getOption('config')) as $configFile) {
                        $this->configFiles[$configFile] = realpath($configFile);
                        if (!Util\File::getFS()->isAbsolutePath($configFile)) {
                            $this->configFiles[$configFile] = realpath(Util::joinFile($this->getPath(), $configFile));
                        }
                    }
                }
                // checks file(s)
                foreach ($this->configFiles as $fileName => $filePath) {
                    if (!file_exists($filePath)) {
                        unset($this->configFiles[$fileName]);
                        $this->getBuilder()->getLogger()->error(\sprintf('Could not find configuration file "%s".', $fileName));
                    }
                }
            }
        }

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            putenv('CECIL_DEBUG=true');

            return parent::run($input, $output);
        }
        // simplified error message
        try {
            return parent::run($input, $output);
        } catch (\Exception $e) {
            if ($this->io === null) {
                $this->io = new SymfonyStyle($input, $output);
            }
            $this->io->error($e->getMessage());

            exit(1);
        }
    }

    /**
     * Returns the working directory.
     */
    protected function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Returns config file(s) path.
     */
    protected function getConfigFiles(): array
    {
        return array_unique($this->configFiles);
    }

    /**
     * Creates or returns a Builder instance.
     *
     * @throws RuntimeException
     */
    protected function getBuilder(array $config = []): Builder
    {
        try {
            $siteConfig = [];
            foreach ($this->getConfigFiles() as $fileName => $filePath) {
                if (false === $configContent = Util\File::fileGetContents($filePath)) {
                    throw new RuntimeException(\sprintf('Can\'t read configuration file "%s".', $fileName));
                }
                $siteConfig = array_replace_recursive($siteConfig, Yaml::parse($configContent));
            }
            $config = array_replace_recursive($siteConfig, $config);

            $this->builder = (new Builder($config, new ConsoleLogger($this->output)))
                ->setSourceDir($this->getPath())
                ->setDestinationDir($this->getPath());
        } catch (ParseException $e) {
            throw new RuntimeException(\sprintf('Configuration parsing error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return $this->builder;
    }

    /**
     * Opens path with editor.
     *
     * @throws RuntimeException
     */
    protected function openEditor(string $path, string $editor): void
    {
        $command = sprintf('%s "%s"', $editor, $path);
        switch (Util\Plateform::getOS()) {
            case Util\Plateform::OS_WIN:
                $command = sprintf('start /B "" %s "%s"', $editor, $path);
                break;
            case Util\Plateform::OS_OSX:
                // Typora on macOS
                if ($editor == 'typora') {
                    $command = sprintf('open -a typora "%s"', $path);
                }
                break;
        }
        $process = Process::fromShellCommandline($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(\sprintf('Can\'t use "%s" editor.', $editor));
        }
    }
}
