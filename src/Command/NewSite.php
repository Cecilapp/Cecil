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

use Cecil\Config;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * NewSite command.
 *
 * This command creates a new website in the specified directory or the current directory if no path is provided.
 * It prompts the user for various details about the website, such as title, baseline, base URL, description, author and language.
 * It also creates a README and a .gitignore file, and can add a GitHub Pages deployment workflow.
 * It can also add demo content if the `--demo` option is provided.
 * If the `--force` option is used, it will override an existing website in the specified directory.
 */
class NewSite extends AbstractCommand
{
    /**
     * Skeleton directory: `base/` files are always copied, `demo/` files with the `--demo` option.
     */
    public const string SKELETON_DIR = 'resources/skeleton';

    /**
     * Skeleton files renamed when copied (dot files are not embedded in the Phar).
     */
    public const array SKELETON_RENAME = [
        'gitignore' => '.gitignore',
    ];

    /**
     * Files not overridden if they already exist (e.g.: in an existing repository).
     */
    public const array SKELETON_PRESERVE = [
        'README.md',
        '.gitignore',
        '.github/workflows/build-and-deploy.yml',
    ];

    /**
     * Additional language of the demo content (translated pages).
     */
    public const array DEMO_LANGUAGE = [
        'code'   => 'fr',
        'name'   => 'Français',
        'locale' => 'fr_FR',
    ];

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function configure()
    {
        $this
            ->setName('new:site')
            ->setDescription('Creates a new website')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override directory if it already exists'),
                new InputOption('demo', null, InputOption::VALUE_NONE, 'Add demo content (pages, templates and assets)'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command creates a new website in the current directory, or in <comment><path></> if provided.
If you run this command without any options, it will ask you for the website title, baseline, base URL, description, author, language, etc.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To create a new website with <comment>demo content</comment>, run:

  <info>%command.full_name% --demo</>

To <comment>override</comment> an existing website, run:

  <info>%command.full_name% --force</>
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
        $force = $input->getOption('force');
        $demo = $input->getOption('demo');

        $this->io->title('Create a new website');
        try {
            // ask to override existing site?
            if (Util\File::getFS()->exists(Util::joinFile($this->getPath(false), $this->locateConfigFile($this->getPath(false))['name'] ?: self::CONFIG_FILE[0])) && !$force) {
                $this->io->warning('Website already exists.');
                if (!$this->io->confirm('Do you want to override it?', false)) {
                    return Command::SUCCESS;
                }
            }
            // setup questions
            $title = $this->io->ask('Give a title to your website', 'New website');
            $baseline = $this->io->ask('Give a baseline to your website', 'A new Cecil website');
            $baseurl = $this->io->ask('Base URL?', '/', $this->validateUrl(...));
            $description = $this->io->ask('Write a full description of your site', 'A website created with Cecil static site generator.');
            $authorName = $this->io->ask('What is the author name? (optional)');
            $authorUrl = $authorName ? $this->io->ask('What is the author URL? (optional)', null, fn ($url) => $url === null ? null : $this->validateUrl($url)) : null;
            $language = $this->io->ask('What is the main language code? (e.g. "en", "fr" or "fr-FR")', 'en', $this->validateLanguageCode(...));
            $demo = ($demo !== false) ?: $this->io->confirm('Add demo content?', false);
            $deploy = $this->io->confirm('Add a GitHub Actions workflow to deploy on GitHub Pages?', false);
            // create path dir
            Util\File::getFS()->mkdir($this->getPath(false));
            // create config file from the skeleton one (to keep comments)
            $configYaml = $this->createConfig(
                Util\File::getFS()->readFile(Util::joinPath($this->rootPath, self::SKELETON_DIR, 'base', self::CONFIG_FILE[0])),
                [
                    'title'       => $title,
                    'baseline'    => $baseline,
                    'baseurl'     => $baseurl,
                    'description' => $description,
                    'author'      => array_filter(['name' => $authorName, 'url' => $authorUrl]),
                    'language'    => $language,
                ],
                $demo
            );
            Util\File::getFS()->dumpFile(Util::joinPath($this->getPath(), $this->locateConfigFile($this->getPath())['name'] ?: self::CONFIG_FILE[0]), $configYaml);
            // create sub dir
            foreach (
                [
                    (string) $this->getBuilder()->getConfig()->get('assets.dir'),
                    (string) $this->getBuilder()->getConfig()->get('layouts.dir'),
                    (string) $this->getBuilder()->getConfig()->get('pages.dir'),
                    (string) $this->getBuilder()->getConfig()->get('static.dir'),
                ] as $value
            ) {
                Util\File::getFS()->mkdir(Util::joinPath($this->getPath(), $value));
            }
            // copy base files (except the config file, already created)
            $this->copySkeleton('base', fn (string $path): bool => $path != self::CONFIG_FILE[0]);
            // demo: copy demo files (translated pages are useless if their language is the main one)
            if ($demo) {
                $this->copySkeleton('demo', fn (string $path): bool => !$this->isDemoLanguage($language) || !str_ends_with($path, '.' . self::DEMO_LANGUAGE['code'] . '.md'));
            }
            // deploy: copy the GitHub Pages workflow
            if ($deploy) {
                $this->copySkeletonFile('deploy/github-pages.yml', '.github/workflows/build-and-deploy.yml');
            }
            // done
            if ($output->isDecorated()) {
                $output->write(\sprintf("\033\143"));
            }
            $this->io->success(\sprintf('Website created at %s', realpath($this->getPath())));
            $this->io->listing([
                'Start the built-in preview server with <info>' . $this->binName() . ' serve</info>',
                'You can create a new page with <info>' . $this->binName() . ' new:page</info>',
            ]);
            if ($deploy) {
                $this->io->text('To deploy on GitHub Pages, select "GitHub Actions" as source in the repository settings, and set <info>baseurl</info> with the GitHub Pages URL.');
            }
            $this->io->text('Visit <href=https://cecil.app>https://cecil.app</> for documentation and more.');
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }

        return Command::SUCCESS;
    }

    /**
     * Validates a language code (e.g.: "en", "fr" or "fr-FR").
     *
     * @throws RuntimeException
     */
    public static function validateLanguageCode(string $code): string
    {
        if (!preg_match('/^' . Config::LANG_CODE_PATTERN . '$/', $code)) {
            throw new RuntimeException(\sprintf('"%s" is not a valid language code (e.g.: "en", "fr" or "fr-FR").', $code));
        }

        return $code;
    }

    /**
     * Creates the configuration YAML from the skeleton one, by replacing values (and keeping comments).
     *
     * @param array<string, mixed> $values
     */
    protected function createConfig(string $yaml, array $values, bool $demo = false): string
    {
        foreach (['title', 'baseline', 'baseurl', 'description'] as $key) {
            $yaml = $this->replaceConfigEntry($yaml, $key, Yaml::dump([$key => $values[$key]]));
        }
        // author: replaces the commented example
        if (!empty($values['author'])) {
            $yaml = preg_replace('/^#author:\R(?:#  [^\r\n]*\R)*/m', Yaml::dump(['author' => $values['author']], 2, 2), $yaml, 1) ?? $yaml;
        }
        // language: the list of languages is required if the main language is not the default one, or with the translated demo content
        $languages = [];
        if ($values['language'] != 'en' || ($demo && !$this->isDemoLanguage($values['language']))) {
            $languages[] = [
                'code'   => $values['language'],
                'name'   => $this->getLanguageName($values['language']),
                'locale' => $values['language'] == 'en' ? 'en_US' : str_replace('-', '_', $values['language']),
            ];
        }
        if ($demo && !$this->isDemoLanguage($values['language'])) {
            $languages[] = self::DEMO_LANGUAGE;
        }
        $language = Yaml::dump(['language' => $values['language']]);
        if (!empty($languages)) {
            $language .= Yaml::dump(['languages' => $languages], 3, 2, Yaml::DUMP_COMPACT_NESTED_MAPPING);
        }

        return $this->replaceConfigEntry($yaml, 'language', $language);
    }

    /**
     * Copies files of a skeleton sub directory (e.g.: `base` or `demo`), accepted by the filter (with their relative path).
     */
    private function copySkeleton(string $dir, ?callable $filter = null): void
    {
        foreach (Finder::create()->files()->in(Util::joinPath($this->rootPath, self::SKELETON_DIR, $dir)) as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());
            if ($filter !== null && !$filter($path)) {
                continue;
            }
            $this->copySkeletonFile(Util::joinPath($dir, $path), self::SKELETON_RENAME[$path] ?? $path);
        }
    }

    /**
     * Copies a skeleton file to the site, unless it must be preserved.
     */
    private function copySkeletonFile(string $source, string $target): void
    {
        $targetPath = Util::joinPath($this->getPath(), $target);
        if (\in_array($target, self::SKELETON_PRESERVE) && Util\File::getFS()->exists($targetPath)) {
            return;
        }
        Util\File::getFS()->copy(Util::joinPath($this->rootPath, self::SKELETON_DIR, $source), $targetPath);
        // files are read-only in the Phar: make them writable (otherwise rebuilds can't overwrite them)
        Util\File::getFS()->chmod($targetPath, 0777 & ~umask());
    }

    /**
     * Replaces a top level entry (one line) of a YAML string.
     */
    private function replaceConfigEntry(string $yaml, string $key, string $entry): string
    {
        return preg_replace('/^' . preg_quote($key, '/') . ':[^\r\n]*\R/m', addcslashes($entry, '\\$'), $yaml, 1) ?? $yaml;
    }

    /**
     * Is the language (or its primary language) the language of the translated demo content?
     */
    private function isDemoLanguage(string $code): bool
    {
        return explode('-', $code)[0] == self::DEMO_LANGUAGE['code'];
    }

    /**
     * Returns the name of a language in this language, if the Intl extension is available.
     */
    private function getLanguageName(string $code): string
    {
        if (!\extension_loaded('intl')) {
            return $code;
        }
        $name = \Locale::getDisplayLanguage($code, $code);

        return $name ? mb_convert_case(mb_substr($name, 0, 1), MB_CASE_UPPER) . mb_substr($name, 1) : $code;
    }
}
