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

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Catalogue\AbstractOperation;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Catalogue\OperationInterface;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TranslationsExtract extends AbstractCommand
{
    private const SORT_ASC = 'asc';
    private const SORT_DESC = 'desc';
    private const SORT_ORDERS = [self::SORT_ASC, self::SORT_DESC];
    private const AVAILABLE_FORMATS = [
        'xlf12' => ['xlf', '1.2'],
        'xlf20' => ['xlf', '2.0'],
        'po' => ['po'],
        'yaml' => ['yaml'],
    ];
    private const ERROR_OPTION_SELECTION = 'You must choose one of --force or --dump-messages option';
    private const ERROR_MISSING_THEME_NAME = 'You must specify a theme name with --name option';
    private const ERROR_WRONG_FORMAT = 'Wrong output format. Supported formats are: %s';
    private const ERROR_WRONG_SORT = 'Wrong sort format. Supported sorts are: %s';

    private TranslationWriterInterface $writer;
    private TranslationReaderInterface $reader;
    private TwigExtractor $extractor;

    protected function configure(): void
    {
        $this
            ->setName('translations:extract')
            ->setDescription('Extracts translations from layouts')
            ->setDefinition([
                new InputArgument('locale', InputArgument::OPTIONAL, 'The locale', 'fr'),
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('as-tree', null, InputOption::VALUE_OPTIONAL, 'Dump the messages as a tree-like structure: The given value defines the level where to switch to inline YAML'),
                new InputOption('clean', null, InputOption::VALUE_NONE, 'Clean not found messages'),
                new InputOption('domain', null, InputOption::VALUE_OPTIONAL, 'Specify the domain to extract'),
                new InputOption('dump-messages', null, InputOption::VALUE_NONE, 'Should the messages be dumped in the console'),
                new InputOption('force', null, InputOption::VALUE_NONE, 'Should the extract be done'),
                new InputOption('format', null, InputOption::VALUE_OPTIONAL, 'Override the default output format', 'po'),
                new InputOption('is-theme', null, InputOption::VALUE_NONE, 'Use if you want to translate a theme layout'),
                new InputOption('name', null, InputOption::VALUE_OPTIONAL, 'The theme name (only works with <info>--is-theme</>)'),
                new InputOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Override the default prefix', '__'),
                new InputOption('sort', null, InputOption::VALUE_OPTIONAL, 'Return list of messages sorted alphabetically (only works with <info>--dump-messages</>)', 'asc'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command extracts translation strings from your layouts. It can display them or merge
the new ones into the translation files.
When new translation strings are found it can automatically add a prefix to the translation message.

Example running against default directory:

  <info>php %command.full_name% --dump-messages</info>
  <info>php %command.full_name% --force --prefix="new_" en</info>

You can sort the output with the <comment>--sort</> flag:

    <info>php %command.full_name% --dump-messages --sort=desc fr</info>
    <info>php %command.full_name% --dump-messages --sort=asc en path/to/sources</info>

You can dump a tree-like structure using the yaml format with <comment>--as-tree</> flag:

    <info>php %command.full_name% --force --format=yaml --as-tree=3 fr</info>
    <info>php %command.full_name% --force --format=yaml --as-tree=3 en path/to/sources</info>

You can extract translations from a given theme with <comment>--is-theme</> and <comment>--name</> flags:

    <info>php %command.full_name% --force --is-theme --name=hyde</info>
EOF
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initializeTranslationComponents();

        $errorIo = $this->io->getErrorStyle();

        $xliffVersion = '1.2';
        $format = $input->getOption('format');

        $config = $this->getBuilder()->getConfig();
        $layoutsPath = $config->getSourceDir();
        $translationsPath = $config->getTranslationsPath();

        try {
            [$format, $xliffVersion] = $this->checkOptions($input, $format, $xliffVersion);
        } catch (\Exception $exception) {
            $errorIo->error($exception->getMessage());
            return self::FAILURE;
        }

        if ($input->getOption('is-theme')) {
            $layoutsPath = $config->getThemeDirPath($input->getOption('name'));
        }

        $this->initializeTwigExtractor($layoutsPath);

        $this->io->title('Translation Messages Extractor and Dumper');
        $this->io->comment(\sprintf('Generating "<info>%s</info>" translation files', $input->getArgument('locale')));

        $this->io->comment('Parsing templates...');
        $extractedCatalogue = $this->extractMessages(
            $input->getArgument('locale'),
            $layoutsPath,
            $input->getOption('prefix')
        );

        $this->io->comment('Loading translation files...');
        $currentCatalogue = $this->loadCurrentMessages($input->getArgument('locale'), $translationsPath);

        if (null !== $domain = $input->getOption('domain')) {
            $currentCatalogue = $this->filterCatalogue($currentCatalogue, $domain);
            $extractedCatalogue = $this->filterCatalogue($extractedCatalogue, $domain);
        }

        try {
            $operation = $this->getOperation($input->getOption('clean'), $currentCatalogue, $extractedCatalogue);
        } catch (\Exception $exception) {
            $errorIo->error($exception->getMessage());
            return self::SUCCESS;
        }

        if ('xlf' === $format) {
            $this->io->comment(\sprintf('Xliff output version is <info>%s</info>', $xliffVersion));
        }

        // Show compiled list of messages
        if (true === $input->getOption('dump-messages')) {
            try {
                $this->dumpMessages($operation, $input->getOption('sort'));
            } catch (\Exception) {
                $errorIo->error(\sprintf(self::ERROR_WRONG_SORT, implode(', ', self::SORT_ORDERS)));
                return self::FAILURE;
            }
        }

        // Save the files
        if (true === $input->getOption('force')) {
            try {
                $this->saveDump(
                    $operation->getResult(),
                    $format,
                    $translationsPath,
                    $config->getLanguageDefault(),
                    $xliffVersion,
                    $input->getOption('as-tree')
                );
            } catch (\InvalidArgumentException $exception) {
                $this->io->error('Error while updating translations files: ' . $exception->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Checks the provided options and validates the format and xliff version.
     *
     * @return array<string, string> An array containing the validated format and xliff version.
     *
     * @throws \Exception If required options are not provided or if the format is not supported.
     */
    private function checkOptions(InputInterface $input, string $format, string $xliffVersion): array
    {
        if (true !== $input->getOption('force') && true !== $input->getOption('dump-messages')) {
            throw new \Exception(self::ERROR_OPTION_SELECTION);
        }

        if (true === $input->getOption('is-theme') && null === $input->getOption('name')) {
            throw new \Exception(self::ERROR_MISSING_THEME_NAME);
        }

        // Get Xliff version
        if (\array_key_exists($format, self::AVAILABLE_FORMATS)) {
            [$format, $xliffVersion] = self::AVAILABLE_FORMATS[$format];
        }

        // Check format
        // @phpstan-ignore-next-line
        $supportedFormats = $this->writer->getFormats();

        if (!\in_array($format, $supportedFormats, true)) {
            throw new \Exception(
                \sprintf(self::ERROR_WRONG_FORMAT, implode(', ', array_keys(self::AVAILABLE_FORMATS)))
            );
        }

        return [$format, $xliffVersion];
    }

    private function initializeTranslationComponents(): void
    {
        $this->writer = new TranslationWriter();
        $this->writer->addDumper('xlf', new XliffFileDumper());
        $this->writer->addDumper('po', new PoFileDumper());
        $this->writer->addDumper('yaml', new YamlFileDumper());

        $this->reader = new TranslationReader();
    }

    private function initializeTwigExtractor(string $layoutsPath): void
    {
        $twig = new Environment(new FilesystemLoader($layoutsPath));
        $twig->addExtension(new TranslationExtension());

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
    private function getOperation(
        ?bool $mustClean,
        MessageCatalogue $currentCatalogue,
        MessageCatalogue $extractedCatalogue
    ): AbstractOperation {
        $operation = $this->processCatalogues($mustClean, $currentCatalogue, $extractedCatalogue);

        // Exit if no messages found.
        if (!\count($operation->getDomains())) {
            throw new \Exception('No translation messages were found.');
        }

        $operation->moveMessagesToIntlDomainsIfPossible('new');

        return $operation;
    }

    private function processCatalogues(
        ?bool $mustClean,
        MessageCatalogueInterface $currentCatalogue,
        MessageCatalogueInterface $extractedCatalogue
    ): AbstractOperation {
        return $mustClean
            ? new TargetOperation($currentCatalogue, $extractedCatalogue)
            : new MergeOperation($currentCatalogue, $extractedCatalogue);
    }

    private function saveDump(
        MessageCatalogueInterface $messageCatalogue,
        string $format,
        string $translationsPath,
        string $defaultLocale,
        ?string $xliffVersion = '1.2',
        ?bool $asTree = false
    ): void {
        $this->io->newLine();
        $this->io->comment('Writing files...');

        $this->writer->write($messageCatalogue, $format, [
            'path' => $translationsPath,
            'default_locale' => $defaultLocale,
            'xliff_version' => $xliffVersion,
            'as_tree' => $asTree,
            'inline' => $asTree ?? 0
        ]);

        $this->io->success('Translations files have been successfully updated.');
    }

    private function dumpMessages(OperationInterface $operation, ?string $sort): void
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

            if ($sort) {
                $sort = strtolower($sort);
                if (!\in_array($sort, self::SORT_ORDERS, true)) {
                    throw new \Exception();
                }

                sort($list); // default sort ASC

                if (self::SORT_DESC === $sort) {
                    rsort($list);
                }
            }

            $this->io->section(
                \sprintf(
                    'Messages extracted for domain "<info>%s</info>" (%d message%s)',
                    $domain,
                    $domainMessagesCount,
                    $domainMessagesCount > 1 ? 's' : ''
                )
            );

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
