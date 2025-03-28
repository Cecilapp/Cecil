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

use Cecil\Builder;
use Cecil\Config;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\ConsoleLogger;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class AbstractCommand extends Command
{
    public const CONFIG_FILE = ['cecil.yml', 'config.yml'];
    public const TMP_DIR = '.cecil';
    public const THEME_CONFIG_FILE = 'config.yml';
    public const EXCLUDED_CMD = ['about', 'new:site', 'self-update'];

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var SymfonyStyle */
    protected $io;

    /** @var null|string */
    private $path = null;

    /** @var array */
    private $configFiles = [];

    /** @var array */
    private $config;

    /** @var Builder */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        // prepare configuration files list
        if (!\in_array($this->getName(), self::EXCLUDED_CMD)) {
            // site config file
            $this->configFiles[$this->locateConfigFile($this->getPath())['name']] = $this->locateConfigFile($this->getPath())['path'];
            // additional config file(s) from --config=<file>
            if ($input->hasOption('config') && $input->getOption('config') !== null) {
                $this->configFiles += $this->locateAdditionalConfigFiles($this->getPath(), (string) $input->getOption('config'));
            }
            // checks file(s)
            $this->configFiles = array_unique($this->configFiles);
            foreach ($this->configFiles as $fileName => $filePath) {
                if ($filePath === false) {
                    unset($this->configFiles[$fileName]);
                    $this->io->warning(\sprintf('Could not find configuration file "%s".', $fileName));
                }
            }
        }

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        // disable debug mode if a verbosity level is specified
        if ($output->getVerbosity() != OutputInterface::VERBOSITY_NORMAL) {
            putenv('CECIL_DEBUG=false');
        }
        // force verbosity level to "debug" in debug mode
        if (getenv('CECIL_DEBUG') == 'true') {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }
        if ($output->isDebug()) {
            // set env. variable in debug mode
            putenv('CECIL_DEBUG=true');

            return parent::run($input, $output);
        }
        // run with simplified error message
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
     * Returns the working path.
     */
    protected function getPath(bool $exist = true): ?string
    {
        if ($this->path === null) {
            try {
                // get working directory by default
                if (false === $this->path = getcwd()) {
                    throw new \Exception('Can\'t get current working directory.');
                }
                // ... or path
                if ($this->input->getArgument('path') !== null) {
                    $this->path = Path::canonicalize($this->input->getArgument('path'));
                }
                // try to get canonicalized absolute path
                if ($exist) {
                    if (realpath($this->path) === false) {
                        throw new \Exception(\sprintf('The given path "%s" is not valid.', $this->path));
                    }
                    $this->path = realpath($this->path);
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        return $this->path;
    }

    /**
     * Returns config file(s) path.
     */
    protected function getConfigFiles(): array
    {
        return $this->configFiles ?? [];
    }

    /**
     * Creates or returns a Builder instance.
     *
     * @throws RuntimeException
     */
    protected function getBuilder(array $config = []): Builder
    {
        try {
            // loads configuration files if not already done
            if ($this->config === null) {
                // loads and merges configuration files
                $configFromFiles = [];
                foreach ($this->getConfigFiles() as $fileName => $filePath) {
                    if (false === $fileContent = Util\File::fileGetContents($filePath)) {
                        throw new RuntimeException(\sprintf('Can\'t read configuration file "%s".', $fileName));
                    }
                    try {
                        $configFromFiles = array_replace_recursive($configFromFiles, (array) Yaml::parse($fileContent, Yaml::PARSE_DATETIME));
                    } catch (ParseException $e) {
                        throw new RuntimeException(\sprintf('"%s" parsing error: %s', $filePath, $e->getMessage()));
                    }
                }
                // merges configuration from $config parameter
                $this->config = array_replace_recursive($configFromFiles, $config);
            }
            // creates builder instance if not already done
            if ($this->builder === null) {
                $this->builder = (new Builder($this->config, new ConsoleLogger($this->output)))
                    ->setSourceDir($this->getPath())
                    ->setDestinationDir($this->getPath());
                // import themes config
                // @todo Move this to Config class
                $themes = (array) $this->builder->getConfig()->getTheme();
                foreach ($themes as $theme) {
                    $themeConfigFile = Util::joinFile($this->builder->getConfig()->getThemesPath(), $theme, self::THEME_CONFIG_FILE);
                    if (Util\File::getFS()->exists($themeConfigFile)) {
                        if (false === $themeFileContent = Util\File::fileGetContents($themeConfigFile)) {
                            throw new ConfigException(\sprintf('Can\'t read file "themes/%s/%s".', $theme, self::THEME_CONFIG_FILE));
                        }
                        $themeConfig = Yaml::parse($themeFileContent, Yaml::PARSE_DATETIME);
                        $this->builder->getConfig()->import($themeConfig ?? [], Config::PRESERVE);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return $this->builder;
    }

    /**
     * Locates the configuration in the given path, as an array of the file name and path, if file exists, otherwise default name and false.
     */
    protected function locateConfigFile(string $path): array
    {
        $config = [
            'name' => self::CONFIG_FILE[0],
            'path' => false,
        ];
        foreach (self::CONFIG_FILE as $configFileName) {
            if (($configFilePath = realpath(Util::joinFile($path, $configFileName))) !== false) {
                $config = [
                    'name' => $configFileName,
                    'path' => $configFilePath,
                ];
            }
        }

        return $config;
    }

    /**
     * Locates additional configuration file(s) from the given list of files, relative to the given path or absolute.
     */
    protected function locateAdditionalConfigFiles(string $path, string $configFilesList): array
    {
        foreach (explode(',', $configFilesList) as $filename) {
            // absolute path
            $config[$filename] = realpath($filename);
            // relative path
            if (!Util\File::getFS()->isAbsolutePath($filename)) {
                $config[$filename] = realpath(Util::joinFile($path, $filename));
            }
        }

        return $config;
    }

    /**
     * Opens path with editor.
     *
     * @throws RuntimeException
     */
    protected function openEditor(string $path, string $editor): void
    {
        $command = \sprintf('%s "%s"', $editor, $path);
        switch (Util\Platform::getOS()) {
            case Util\Platform::OS_WIN:
                $command = \sprintf('start /B "" %s "%s"', $editor, $path);
                break;
            case Util\Platform::OS_OSX:
                // Typora on macOS
                if ($editor == 'typora') {
                    $command = \sprintf('open -a typora "%s"', $path);
                }
                break;
        }
        $process = Process::fromShellCommandline($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(\sprintf('Can\'t use "%s" editor.', $editor));
        }
    }

    /**
     * Validate URL.
     *
     * @throws RuntimeException
     */
    public static function validateUrl(string $url): string
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($url, new Url());
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new RuntimeException($violation->getMessage());
            }
        }
        return rtrim($url, '/') . '/';
    }

    /**
     * Returns the "binary name" in the console context.
     */
    protected function binName(): string
    {
        return basename($_SERVER['argv'][0]);
    }

    /**
     * Override default help message.
     *
     * @return string
     */
    public function getProcessedHelp(): string
    {
        $name = $this->getName();
        $placeholders = [
            '%command.name%',
            '%command.full_name%',
        ];
        $replacements = [
            $name,
            $this->binName() . ' ' . $name,
        ];

        return str_replace($placeholders, $replacements, $this->getHelp() ?: $this->getDescription());
    }
}
