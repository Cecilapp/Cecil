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

namespace Cecil\Step\Pages;

use Cecil\Builder;
use Cecil\Collection\Page\Collection;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Config as RendererConfig;
use Cecil\Renderer\Layout;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Renderer\Site;
use Cecil\Renderer\Twig;
use Cecil\Renderer\Twig\TwigFactory;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use Psr\Log\LoggerInterface;

/**
 * Render step.
 *
 * This step is responsible for rendering pages using Twig templates.
 * It processes each page, applies the appropriate templates, and generates
 * the final output formats. It also handles subsets of pages if specified,
 * and adds global variables to the renderer. The rendered pages are then
 * stored in the builder's pages collection for further processing or output.
 */
class Render extends AbstractStep
{
    public const TMP_DIR = '.cecil';

    protected $subset = [];
    
    private TwigFactory $twigFactory;

    public function __construct(
        Builder $builder,
        Config $config,
        LoggerInterface $logger,
        TwigFactory $twigFactory
    ) {
        parent::__construct($builder, $config, $logger);
        $this->twigFactory = $twigFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Rendering pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if (!is_dir($this->config->getLayoutsPath()) && !$this->config->hasTheme()) {
            $message = \sprintf('"%s" is not a valid layouts directory', $this->config->getLayoutsPath());
            $this->logger->debug($message);
        }

        // render a subset of pages?
        if (!empty($options['render-subset'])) {
            $subset = \sprintf('pages.subsets.%s', (string) $options['render-subset']);
            if (!$this->config->has($subset)) {
                throw new ConfigException(\sprintf('Subset "%s" not found.', $subset));
            }
            $this->subset = (array) $this->config->get($subset);
        }

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(): void
    {
        // prepares renderer
        $this->builder->setRenderer($this->twigFactory->create($this->getAllLayoutsPaths()));

        // adds global variables
        $this->addGlobals();

        $subset = $this->subset;

        /** @var Collection $pages */
        $pages = $this->builder->getPages()
            // published only
            ->filter(function (Page $page) {
                return (bool) $page->getVariable('published');
            })
            ->filter(function (Page $page) use ($subset) {
                if (empty($subset)) {
                    return true;
                }
                if (
                    !empty($subset['path'])
                    && !((bool) preg_match('/' . (string) $subset['path'] . '/i', $page->getPath()))
                ) {
                    return false;
                }
                if (!empty($subset['language'])) {
                    $language = $page->getVariable('language', $this->config->getLanguageDefault());
                    if ($language !== (string) $subset['language']) {
                        return false;
                    }
                }
                return true;
            })
            // enrichs some variables
            ->map(function (Page $page) {
                $formats = $this->getOutputFormats($page);
                // output formats
                $page->setVariable('output', $formats);
                // alternates formats
                $page->setVariable('alternates', $this->getAlternates($formats));
                // translations
                $page->setVariable('translations', $this->getTranslations($page));

                return $page;
            });
        $total = \count($pages);

        // renders each page
        $count = 0;
        $postprocessors = [];
        foreach ((array) $this->config->get('output.postprocessors') as $name => $postprocessor) {
            try {
                if (!class_exists($postprocessor)) {
                    throw new RuntimeException(\sprintf('Class "%s" not found', $postprocessor));
                }
                $postprocessors[] = new $postprocessor($this->builder);
                $this->logger->debug(\sprintf('Output post processor "%s" loaded', $name));
            } catch (\Exception $e) {
                $this->logger->error(\sprintf('Unable to load output post processor "%s": %s', $name, $e->getMessage()));
            }
        }

        // some cache to avoid multiple calls
        $cache = [];

        /** @var Page $page */
        foreach ($pages as $page) {
            $count++;
            $rendered = [];

            // l10n
            $language = $page->getVariable('language', $this->config->getLanguageDefault());
            if (!isset($cache['locale'][$language])) {
                $cache['locale'][$language] = $this->config->getLanguageProperty('locale', $language);
            }
            $this->builder->getRenderer()->setLocale($cache['locale'][$language]);

            // global site variables
            if (!isset($cache['site'][$language])) {
                $cache['site'][$language] = new Site($this->builder, $language);
            }
            $this->builder->getRenderer()->addGlobal('site', $cache['site'][$language]);

            // global config raw variables
            if (!isset($cache['config'][$language])) {
                $cache['config'][$language] = new RendererConfig($this->builder, $language);
            }
            $this->builder->getRenderer()->addGlobal('config', $cache['config'][$language]);

            // excluded format(s)?
            $formats = (array) $page->getVariable('output');
            foreach ($formats as $key => $format) {
                if ($exclude = $this->config->getOutputFormatProperty($format, 'exclude')) {
                    // ie:
                    //   formats:
                    //     atom:
                    //       [...]
                    //       exclude: [paginated]
                    if (!\is_array($exclude)) {
                        $exclude = [$exclude];
                    }
                    foreach ($exclude as $variable) {
                        if ($page->hasVariable($variable)) {
                            unset($formats[$key]);
                        }
                    }
                }
            }

            // specific output format from subset
            if (!empty($this->subset['output'])) {
                $currentFormats = $formats;
                $formats = [];
                if (\in_array((string) $this->subset['output'], $currentFormats)) {
                    $formats = [(string) $this->subset['output']];
                }
            }

            // renders each output format
            foreach ($formats as $format) {
                // search for the template
                $layout = Layout::finder($page, $format, $this->config);
                // renders with Twig
                try {
                    $deprecations = [];
                    set_error_handler(function ($type, $msg) use (&$deprecations) {
                        if (E_USER_DEPRECATED === $type) {
                            $deprecations[] = $msg;
                        }
                    });
                    $output = $this->builder->getRenderer()->render($layout['file'], ['page' => $page]);
                    foreach ($deprecations as $value) {
                        $this->builder->getLogger()->warning($value);
                    }
                    foreach ($postprocessors as $postprocessor) {
                        $output = $postprocessor->process($page, $output, $format);
                    }
                    $rendered[$format] = [
                        'output'   => $output,
                        'template' => [
                            'scope' => $layout['scope'],
                            'file'  => $layout['file'],
                        ],
                    ];
                    $page->addRendered($rendered);
                } catch (\Twig\Error\Error $e) {
                    throw new RuntimeException(
                        \sprintf(
                            'Unable to render template "%s" for page "%s".',
                            $e->getSourceContext()->getName(),
                            $page->getFileName() ?? $page->getId()
                        ),
                        previous: $e,
                        file: $e->getSourceContext()->getPath(),
                        line: $e->getTemplateLine(),
                    );
                } catch (\Exception $e) {
                    throw new RuntimeException($e->getMessage(), previous: $e);
                }
            }
            $this->builder->getPages()->replace($page->getId(), $page);

            $templates = array_column($rendered, 'template');
            $message = \sprintf(
                'Page "%s" rendered with [%s]',
                $page->getId() ?: 'index',
                Util\Str::combineArrayToString($templates, 'scope', 'file')
            );
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
        // profiler
        if ($this->builder->isDebug()) {
            try {
                // HTML
                $htmlDumper = new \Twig\Profiler\Dumper\HtmlDumper();
                $profileHtmlFile = Util::joinFile($this->config->getDestinationDir(), self::TMP_DIR, 'twig_profile.html');
                Util\File::getFS()->dumpFile($profileHtmlFile, $htmlDumper->dump($this->builder->getRenderer()->getDebugProfile()));
                // TXT
                $textDumper = new \Twig\Profiler\Dumper\TextDumper();
                $profileTextFile = Util::joinFile($this->config->getDestinationDir(), self::TMP_DIR, 'twig_profile.txt');
                Util\File::getFS()->dumpFile($profileTextFile, $textDumper->dump($this->builder->getRenderer()->getDebugProfile()));
                // log
                $this->builder->getLogger()->debug(\sprintf('Twig profile dumped in "%s"', Util::joinFile($this->config->getDestinationDir(), self::TMP_DIR)));
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                throw new RuntimeException($e->getMessage());
            }
        }
    }

    /**
     * Returns an array of layouts directories.
     */
    protected function getAllLayoutsPaths(): array
    {
        $paths = [];

        // layouts/
        if (is_dir($this->config->getLayoutsPath())) {
            $paths[] = $this->config->getLayoutsPath();
        }
        // <theme>/layouts/
        if ($this->config->hasTheme()) {
            foreach ($this->config->getTheme() ?? [] as $theme) {
                $paths[] = $this->config->getThemeDirPath($theme);
            }
        }
        // resources/layouts/
        if (is_dir($this->config->getLayoutsInternalPath())) {
            $paths[] = $this->config->getLayoutsInternalPath();
        }

        return $paths;
    }

    /**
     * Adds global variables.
     */
    protected function addGlobals()
    {
        $this->builder->getRenderer()->addGlobal('cecil', [
            'url'       => \sprintf('https://cecil.app/#%s', Builder::getVersion()),
            'version'   => Builder::getVersion(),
            'poweredby' => \sprintf('Cecil v%s', Builder::getVersion()),
        ]);
    }

    /**
     * Get available output formats.
     *
     * @throws RuntimeException
     */
    protected function getOutputFormats(Page $page): array
    {
        // Get page output format(s) if defined.
        // ie:
        // ```yaml
        // output: txt
        // ```
        if ($page->getVariable('output')) {
            $formats = $page->getVariable('output');
            if (!\is_array($formats)) {
                $formats = [$formats];
            }

            return $formats;
        }

        // Get available output formats for the page type.
        // ie:
        // ```yaml
        // page: [html, json]
        // ```
        $formats = $this->config->get('output.pagetypeformats.' . $page->getType());
        if (empty($formats)) {
            throw new RuntimeException('Configuration key "pagetypeformats" can\'t be empty.');
        }
        if (!\is_array($formats)) {
            $formats = [$formats];
        }

        return array_unique($formats);
    }

    /**
     * Get alternates.
     */
    protected function getAlternates(array $formats): array
    {
        $alternates = [];

        if (\count($formats) > 1 || \in_array('html', $formats)) {
            foreach ($formats as $format) {
                $format == 'html' ? $rel = 'canonical' : $rel = 'alternate';
                $alternates[] = [
                    'rel'    => $rel,
                    'type'   => $this->config->getOutputFormatProperty($format, 'mediatype'),
                    'title'  => strtoupper($format),
                    'format' => $format,
                ];
            }
        }

        return $alternates;
    }

    /**
     * Returns the collection of translated pages for a given page.
     */
    protected function getTranslations(Page $refPage): Collection
    {
        $pages = $this->builder->getPages()->filter(function (Page $page) use ($refPage) {
            return $page->getVariable('langref') == $refPage->getVariable('langref')
                && $page->getType() == $refPage->getType()
                && $page->getId() !== $refPage->getId()
                && !empty($page->getVariable('published'))
                && !$page->getVariable('paginated')
            ;
        });

        return $pages;
    }
}
