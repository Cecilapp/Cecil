<?php

namespace Cecil\Command;

use Cecil\Builder;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Command extends BaseCommand
{
    const CONFIG_FILE = 'config.yml';

    /**
     * @var string
     */
    protected $path;
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->path = $input->getArgument('path');
        $this->path = realpath($this->path);
        $this->path = str_replace(DIRECTORY_SEPARATOR, '/', $this->path);

        parent::initialize($input, $output);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param OutputInterface $output
     * @param array $config
     * @param array $options
     *
     * @return Builder
     */
    public function getBuilder(
        OutputInterface $output,
        array $config = ['debug' => false],
        array $options = ['verbosity' => Builder::VERBOSITY_NORMAL]
    ) {
        if (!file_exists($this->getPath() . '/' . self::CONFIG_FILE)) {
            throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
        }
        // verbosity: verbose
        if ($options['verbosity'] == Builder::VERBOSITY_VERBOSE) {
            $this->verbose = true;
        }
        // verbosity: quiet
        if ($options['verbosity'] == Builder::VERBOSITY_QUIET) {
            $this->quiet = true;
        }

        try {
            $configFile = Yaml::parse(file_get_contents($this->getPath() . '/' . self::CONFIG_FILE));
            $config = array_replace_recursive($configFile, $config);
            $this->builder = (new Builder($config, $this->messageCallback($output)))
                ->setSourceDir($this->getPath())
                ->setDestinationDir($this->getPath());
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Config file parse error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        return $this->builder;
    }

    /**
     * Custom message callback function.
     *
     * @param OutputInterface $output
     */
    public function messageCallback(OutputInterface $output)
    {
        return function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) use ($output) {
            if ($this->quiet) {
                return;
            } else {
                if (strpos($code, '_PROGRESS') !== false) {
                    if ($this->verbose) {
                        if ($itemsCount > 0) {
                            //$this->wlDone(sprintf('(%u/%u) %s', $itemsCount, $itemsMax, $message));
                            $output->writeln(sprintf('(%u/%u) %s', $itemsCount, $itemsMax, $message));

                            return;
                        }
                        //$this->wlDone("$message");
                        $output->writeln("$message");
                    } else {
                        if (isset($itemsCount) && $itemsMax > 0) {
                            //$this->printProgressBar($itemsCount, $itemsMax, $message);
                            $output->writeln("$message");
                        } else {
                            //$this->wl($message);
                            $output->writeln("$message");
                        }
                    }
                } elseif (strpos($code, '_ERROR') !== false) {
                    //$this->wlError($message);
                    $output->writeln("$message");
                } elseif ($code == 'TIME') {
                    //$this->wl($message);
                    $output->writeln("$message");
                } else {
                    //$this->wlAnnonce($message);
                    $output->writeln("$message");
                }
            }
        };
    }
}
