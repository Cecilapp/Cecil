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

use Cecil\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * ShowConfig command.
 *
 * This command displays the website's configuration in YAML format.
 * It can be used to quickly review the current configuration settings.
 */
class ShowConfig extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function configure()
    {
        $this
            ->setName('show:config')
            ->setDescription('Shows the configuration')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
                new InputOption('key', 'k', InputOption::VALUE_REQUIRED, 'Show only the given configuration key (dot notation, e.g. "pages.dir")'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command shows the website\'s configuration in YAML format.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To show the configuration with an extra configuration file, run:

  <info>%command.full_name% --config=config.yml</>

To show only a specific configuration key (dot notation), run:

  <info>%command.full_name% --key=baseurl</>
  <info>%command.full_name% --key=pages.dir</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $config = $this->getBuilder()->getConfig();
            if (null !== $key = $input->getOption('key')) {
                if (!$config->has($key)) {
                    throw new RuntimeException(\sprintf('Configuration key "%s" not found.', $key));
                }
                $output->writeln($this->toYaml($config->get($key)));

                return Command::SUCCESS;
            }
            $output->writeln($this->toYaml($config->export()));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * Converts a value to YAML.
     */
    private function toYaml(mixed $value): string
    {
        return trim(Yaml::dump($value, 6, 2));
    }
}
