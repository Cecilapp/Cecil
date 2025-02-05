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

class UtilTranslationsExtract extends AbstractCommand
{
    private TranslationWriter $writer;
    private TranslationReader $reader;
    private TwigExtractor $extractor;

    protected function configure(): void
    {
        $this
            ->setName('util:translations:extract')
            ->setDescription('Extracts translations from layouts')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('locale', null, InputOption::VALUE_OPTIONAL, 'The locale', 'fr'),
                new InputOption('show', null, InputOption::VALUE_NONE, 'Should the messages be displayed in the console'),
                new InputOption('save', null, InputOption::VALUE_NONE, 'Should the extract be done'),
                new InputOption('format', null, InputOption::VALUE_OPTIONAL, 'Override the default output format', 'po'),
                new InputOption('theme', null, InputOption::VALUE_OPTIONAL, 'Use if you want to translate a theme layouts too'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command extracts translation strings from your layouts.
It can display them or merge the new ones into the translation file.
When new translation strings are found it automatically add a <info>NEW_</info> prefix to the translation message.

Example running against working directory:

  <info>php %command.full_name% --show</info>
  <info>php %command.full_name% --save --locale=en</info>

You can extract, and merge, translations from a given theme with <comment>--theme</> option:

  <info>php %command.full_name% --show --theme=hyde</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getBuilder()->getConfig();
        $layoutsPath = $config->getLayoutsPath();
        $translationsPath = $config->getTranslationsPath();

        $this->initializeTranslationComponents();

        $this->checkOptions($input);

        if ($input->getOption('theme')) {
            $layoutsPath = [$layoutsPath, $config->getThemeDirPath($input->getOption('theme'))];
        }

        $this->initializeTwigExtractor($layoutsPath);

        $output->writeln(\sprintf('Generating "<info>%s</info>" translation file', $input->getOption('locale')));

        $output->writeln('Parsing templates...');
        $extractedCatalogue = $this->extractMessages($input->getOption('locale'), $layoutsPath, 'NEW_');

        $output->writeln('Loading translation file...');
        $currentCatalogue = $this->loadCurrentMessages($input->getOption('locale'), $translationsPath);

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
                $this->saveDump(
                    $operation->getResult(),
                    $input->getOption('format'),
                    $translationsPath,
                    $config->getLanguageDefault()
                );
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

    private function initializeTranslationComponents(): void
    {
        $this->reader = new TranslationReader();
        $this->reader->addLoader('po', new PoFileLoader());
        $this->reader->addLoader('yaml', new YamlFileLoader());
        $this->writer = new TranslationWriter();
        $this->writer->addDumper('po', new PoFileDumper());
        $this->writer->addDumper('yaml', new YamlFileDumper());
    }

    private function initializeTwigExtractor($layoutsPath = []): void
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

    private function saveDump(MessageCatalogueInterface $messageCatalogue, string $format, string $translationsPath, string $defaultLocale): void
    {
        $this->io->writeln('Writing file...');
        $this->writer->write($messageCatalogue, $format, [
            'path' => $translationsPath,
            'default_locale' => $defaultLocale,
        ]);
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
            \sprintf(
                '%d message%s successfully extracted.',
                $messagesCount,
                $messagesCount > 1 ? 's were' : ' was'
            )
        );
    }
}
