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
use Cecil\Logger\ConsoleLogger;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
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
    /** @var string */
    protected $configFile;
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
            $this->path = (string) $input->getArgument('path');
            if (null === $this->getPath()) {
                $this->path = getcwd();
            }
            if (false === realpath($this->getPath())) {
                $this->fs->mkdir($this->getPath());
            }
            $this->path = realpath($this->getPath());
            $this->configFile = Util::joinFile($this->getPath(), self::CONFIG_FILE);
            if (!in_array($this->getName(), ['new:site'])) {
                if (!file_exists($this->configFile)) {
                    $message = sprintf('Could not find "%s" file in "%s"', self::CONFIG_FILE, $this->getPath());
                    $this->getBuilder()->getLogger()->debug($message);
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
            parent::run($input, $output);
        }
        // simplifying error message
        try {
            parent::run($input, $output);
        } catch (\Exception $e) {
            if ($this->io === null) {
                $this->io = new SymfonyStyle($input, $output);
            }
            $this->io->error($e->getMessage());
        }
    }

    /**
     * Returns the working directory.
     *
     * @return string|null
     */
    protected function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Creates or returns a Builder instance.
     *
     * @param array $config
     *
     * @return Builder
     */
    protected function getBuilder(array $config = []): Builder
    {
        try {
            if (file_exists($this->configFile)) {
                $configContent = Util::fileGetContents($this->configFile);
                if ($configContent === false) {
                    throw new \Exception('Can\'t read the configuration file.');
                }
                $siteConfig = Yaml::parse($configContent);
                $config = array_replace_recursive($siteConfig, $config);
            }
            $this->builder = (new Builder($config, new ConsoleLogger($this->output)))
                ->setSourceDir($this->getPath())
                ->setDestinationDir($this->getPath());
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Configuration file parse error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this->builder;
    }
}
