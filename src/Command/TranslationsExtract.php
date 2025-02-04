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
use Cecil\Renderer\Extension\Core as CoreExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Catalogue\AbstractOperation;
use Symfony\Component\Translation\Catalogue\OperationInterface;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TranslationsExtract extends AbstractCommand
{
    private const AVAILABLE_FORMATS = [
        'po' => ['po'],
        'yaml' => ['yaml'],
    ];
    private TranslationWriterInterface $writer;
    private TranslationReaderInterface $reader;
    private TwigExtractor $extractor;

    protected function configure(): void
    {
        $this
            ->setName('translations:extract')
            ->setDescription('Extracts translations from layouts')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('locale', null, InputOption::VALUE_OPTIONAL, 'The locale', 'fr'),
                new InputOption('show', null, InputOption::VALUE_NONE, 'Should the messages be displayed in the console'),
                new InputOption('save', null, InputOption::VALUE_NONE, 'Should the extract be done'),
                new InputOption('format', null, InputOption::VALUE_OPTIONAL, 'Override the default output format', 'po'),
                new InputOption('theme', null, InputOption::VALUE_OPTIONAL, 'Use if you want to translate a theme layout'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command extracts translation strings from your layouts. It can display them or merge
the new ones into the translation files.
When new translation strings are found it automatically add a <info>NEW_</info> prefix to the translation message.

Example running against working directory:

  <info>php %command.full_name% --show</info>
  <info>php %command.full_name% --save --locale=en</info>

You can extract translations from a given theme with <comment>--theme</> option:

  <info>php %command.full_name% --theme=hyde</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        $domain = 'messages';

        if (true !== $input->getOption('save') && true !== $input->getOption('show')) {
            throw new RuntimeException('You must choose to display (`--show`) or save (`--save`) the translations');
        }

        $config = $this->getBuilder()->getConfig();
        $layoutsPath = $config->getLayoutsPath();
        $translationsPath = $config->getTranslationsPath();

        $this->initializeTranslationComponents();

        // @phpstan-ignore-next-line
        $supportedFormats = $this->writer->getFormats();

        if (!\in_array($format, $supportedFormats, true)) {
            throw new RuntimeException(\sprintf('Supported formats are: %s', implode(', ', array_keys(self::AVAILABLE_FORMATS))));
        }

        if ($input->getOption('theme')) {
            $layoutsPath = $config->getThemeDirPath($input->getOption('theme'));
        }

        $this->initializeTwigExtractor($layoutsPath);

        $this->io->writeln(\sprintf('Generating "<info>%s</info>" translation files', $input->getOption('locale')));
        $this->io->writeln('Parsing templates...');
        $extractedCatalogue = $this->extractMessages($input->getOption('locale'), $layoutsPath, 'NEW_');
        $this->io->writeln('Loading translation files...');
        $currentCatalogue = $this->loadCurrentMessages($input->getOption('locale'), $translationsPath);

        $currentCatalogue = $this->filterCatalogue($currentCatalogue, $domain);
        $extractedCatalogue = $this->filterCatalogue($extractedCatalogue, $domain);
        try {
            $operation = $this->getOperation($currentCatalogue, $extractedCatalogue);
        } catch (\Exception $exception) {
            throw new RuntimeException($exception->getMessage());
        }

        // show compiled list of messages
        if (true === $input->getOption('show')) {
            try {
                $this->dumpMessages($operation);
            } catch (\Exception $e) {
                throw new RuntimeException('Error while displaying messages: ' . $e->getMessage());
            }
        }

        // save the files
        if (true === $input->getOption('save')) {
            try {
                $this->saveDump(
                    $operation->getResult(),
                    $format,
                    $translationsPath,
                    $config->getLanguageDefault()
                );
            } catch (\InvalidArgumentException $e) {
                throw new RuntimeException('Error while saving translations files: ' . $e->getMessage());
            }
        }

        return 0;
    }

    private function initializeTranslationComponents(): void
    {
        // readers
        $this->reader = new TranslationReader();
        $this->reader->addLoader('po', new PoFileLoader());
        $this->reader->addLoader('yaml', new YamlFileLoader());
        // writers
        $this->writer = new TranslationWriter();
        $this->writer->addDumper('po', new PoFileDumper());
        $this->writer->addDumper('yaml', new YamlFileDumper());
    }

    private function initializeTwigExtractor(string $layoutsPath): void
    {
        $twig = new Environment(new FilesystemLoader($layoutsPath));
        $twig->addExtension(new TranslationExtension());
        $twig->addExtension(new CoreExtension($this->getBuilder()));
        $this->extractor = new TwigExtractor($twig);
    }

    private function extractMessages(string $locale, string $codePath, string $prefix): MessageCatalogue
    {
        $extractedCatalogue = new MessageCatalogue($locale);
        $this->extractor->setPrefix($prefix);
        if (is_dir($codePath) || is_file($codePath)) {
            $this->extractor->extract($codePath, $extractedCatalogue);
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

    private function filterCatalogue(MessageCatalogue $catalogue, string $domain): MessageCatalogue
    {
        $filteredCatalogue = new MessageCatalogue($catalogue->getLocale());

        // extract intl-icu messages only
        $intlDomain = $domain . MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;
        if ($intlMessages = $catalogue->all($intlDomain)) {
            $filteredCatalogue->add($intlMessages, $intlDomain);
        }

        // extract all messages and subtract intl-icu messages
        if ($messages = array_diff($catalogue->all($domain), $intlMessages)) {
            $filteredCatalogue->add($messages, $domain);
        }
        foreach ($catalogue->getResources() as $resource) {
            $filteredCatalogue->addResource($resource);
        }

        if ($metadata = $catalogue->getMetadata('', $intlDomain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $intlDomain);
            }
        }

        if ($metadata = $catalogue->getMetadata('', $domain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $domain);
            }
        }

        return $filteredCatalogue;
    }

    /**
     * Retrieves the operation that processes the current and extracted message catalogues.
     *
     * @throws \Exception If no translation messages are found.
     */
    private function getOperation(MessageCatalogue $currentCatalogue, MessageCatalogue $extractedCatalogue): AbstractOperation
    {
        $operation = $this->processCatalogues($currentCatalogue, $extractedCatalogue);
        if (!\count($operation->getDomains())) {
            throw new RuntimeException('No translation messages were found.');
        }

        return $operation;
    }

    private function processCatalogues(MessageCatalogueInterface $currentCatalogue, MessageCatalogueInterface $extractedCatalogue): AbstractOperation
    {
        return new TargetOperation($currentCatalogue, $extractedCatalogue);
    }

    private function saveDump(MessageCatalogueInterface $messageCatalogue, string $format, string $translationsPath, string $defaultLocale): void
    {
        $this->io->newLine();
        $this->io->writeln('Writing files...');

        $this->writer->write($messageCatalogue, $format, [
            'path' => $translationsPath,
            'default_locale' => $defaultLocale,
        ]);

        $this->io->success('Translations files have been successfully updated.');
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
            sort($list); // default sort ASC
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
