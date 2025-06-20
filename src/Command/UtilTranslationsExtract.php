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
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Catalogue\OperationInterface;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;

/**
 * UtilTranslationsExtract command.
 *
 * This command extracts translation strings from templates and allows saving them into a translation file.
 * It can also display the extracted messages in the console.
 */
class UtilTranslationsExtract extends AbstractCommand
{
    private TranslationWriter $writer;
    private TranslationReader $reader;
    private TwigExtractor $extractor;

    protected function configure(): void
    {
        $this
            ->setName('util:translations:extract')
            ->setDescription('Extracts translations from templates')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('locale', null, InputOption::VALUE_REQUIRED, 'Set the locale', 'fr'),
                new InputOption('show', null, InputOption::VALUE_NONE, 'Display translation messages in the console, as a list'),
                new InputOption('save', null, InputOption::VALUE_NONE, 'Save translation messages into the translation file'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Override the default output format', 'po'),
                new InputOption('theme', null, InputOption::VALUE_REQUIRED, 'Merge translation messages from a given theme'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command extracts translation strings from your templates.

  <info>%command.full_name% --locale=code --show</>
  <info>%command.full_name% --locale=code --show path/to/the/working/directory</>

To <comment>save</comment> translations into the translation file, run:

  <info>%command.full_name% --locale=code --save</>

To save translations into a specific <comment>format</comment>, run:

  <info>%command.full_name% --locale=code --save --format=po</>

To extract and merge translations from a specific <comment>theme</comment>, run:

  <info>%command.full_name% --locale=code --show --theme=theme-name</>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getBuilder()->getConfig();
        $layoutsPath = $config->getLayoutsPath();
        $translationsPath = $config->getTranslationsPath();

        $this->initTranslationComponents();

        $this->checkOptions($input);

        if ($input->getOption('theme')) {
            $layoutsPath = [$layoutsPath, $config->getThemeDirPath($input->getOption('theme'))];
        }

        $this->initTwigExtractor($layoutsPath);

        $output->writeln(\sprintf('Generating "<info>%s</info>" translation file', $input->getOption('locale')));

        $output->writeln('Parsing templates...');
        $extractedCatalogue = $this->extractMessages($input->getOption('locale'), $layoutsPath, 'NEW_');

        $output->writeln('Loading translation file...');
        $currentCatalogue = $this->loadCurrentMessages($input->getOption('locale'), $translationsPath);

        // processing translations catalogues
        try {
            $operation = $input->getOption('theme')
                ? new MergeOperation($currentCatalogue, $extractedCatalogue)
                : new TargetOperation($currentCatalogue, $extractedCatalogue);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        // show compiled list of messages
        if (true === $input->getOption('show')) {
            try {
                $this->dumpMessages($operation);
            } catch (\Exception $e) {
                throw new RuntimeException('Error while displaying messages: ' . $e->getMessage());
            }
        }

        // save the file
        if (true === $input->getOption('save')) {
            try {
                $this->saveDump($operation->getResult(), $input->getOption('format'), $translationsPath);
            } catch (\InvalidArgumentException $e) {
                throw new RuntimeException('Error while saving translation file: ' . $e->getMessage());
            }
        }

        return 0;
    }

    private function checkOptions(InputInterface $input): void
    {
        if (true !== $input->getOption('save') && true !== $input->getOption('show')) {
            throw new RuntimeException('You must choose to display (`--show`) and/or save (`--save`) the translations');
        }
        if (!\in_array($input->getOption('format'), $this->writer->getFormats(), true)) {
            throw new RuntimeException(\sprintf('Supported formats are: %s', implode(', ', $this->writer->getFormats())));
        }
    }

    private function initTranslationComponents(): void
    {
        $this->reader = new TranslationReader();
        $this->reader->addLoader('po', new PoFileLoader());
        $this->reader->addLoader('yaml', new YamlFileLoader());
        $this->writer = new TranslationWriter();
        $this->writer->addDumper('po', new PoFileDumper());
        $this->writer->addDumper('yaml', new YamlFileDumper());
    }

    private function initTwigExtractor($layoutsPath = []): void
    {
        $twig = (new \Cecil\Renderer\Twig($this->getBuilder(), $layoutsPath))->getTwig();
        $this->extractor = new TwigExtractor($twig);
    }

    private function extractMessages(string $locale, $layoutsPath, string $prefix): MessageCatalogue
    {
        $extractedCatalogue = new MessageCatalogue($locale);
        $this->extractor->setPrefix($prefix);
        $layoutsPath = \is_array($layoutsPath) ? $layoutsPath : [$layoutsPath];
        foreach ($layoutsPath as $path) {
            $this->extractor->extract($path, $extractedCatalogue);
        }

        return $extractedCatalogue;
    }

    private function loadCurrentMessages(string $locale, string $translationsPath): MessageCatalogue
    {
        $currentCatalogue = new MessageCatalogue($locale);
        if (is_dir($translationsPath)) {
            $this->reader->read($translationsPath, $currentCatalogue);
        }

        return $currentCatalogue;
    }

    private function saveDump(MessageCatalogueInterface $messageCatalogue, string $format, string $translationsPath): void
    {
        $this->io->writeln('Writing file...');
        $this->writer->write($messageCatalogue, $format, ['path' => $translationsPath]);
        $this->io->success('Translation file have been successfully updated.');
    }

    private function dumpMessages(OperationInterface $operation): void
    {
        $messagesCount = 0;
        $this->io->newLine();
        foreach ($operation->getDomains() as $domain) {
            $newKeys = array_keys($operation->getNewMessages($domain));
            $allKeys = array_keys($operation->getMessages($domain));
            $list = array_merge(
                array_diff($allKeys, $newKeys),
                array_map(fn ($key) => \sprintf('<fg=green>%s</>', $key), $newKeys),
                array_map(
                    fn ($key) => \sprintf('<fg=red>%s</>', $key),
                    array_keys($operation->getObsoleteMessages($domain))
                )
            );
            $domainMessagesCount = \count($list);
            sort($list);
            $this->io->listing($list);
            $messagesCount += $domainMessagesCount;
        }

        $this->io->success(
            \sprintf('%d message%s successfully extracted.', $messagesCount, $messagesCount > 1 ? 's were' : ' was')
        );
    }
}
