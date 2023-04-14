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

namespace Cecil\Step\Pages;

use Cecil\Builder;
use Cecil\Collection\Page\Collection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\PrefixSuffix;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Layout;
use Cecil\Renderer\Site;
use Cecil\Renderer\Twig;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Pages rendering.
 */
class Render extends AbstractStep
{
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
            $message = \sprintf("'%s' is not a valid layouts directory", $this->config->getLayoutsPath());
            $this->builder->getLogger()->debug($message);
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
        $this->builder->setRenderer(new Twig($this->builder, $this->getAllLayoutsPaths()));

        // adds global variables
        $this->addGlobals();

        /** @var Collection $pages */
        $pages = $this->builder->getPages()
            // published only
            ->filter(function (Page $page) {
                return (bool) $page->getVariable('published');
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
        $total = count($pages);

        // renders each page
        $count = 0;
        /** @var Page $page */
        foreach ($pages as $page) {
            $count++;
            $rendered = [];

            // l10n
            $language = $page->getVariable('language', $this->config->getLanguageDefault());
            $locale = $this->config->getLanguageProperty('locale', $language);
            $this->builder->getRenderer()->setLocale($locale);

            // global site variables
            $this->builder->getRenderer()->addGlobal('site', new Site($this->builder, $language));

            // excluded format(s)?
            $formats = (array) $page->getVariable('output');
            foreach ($formats as $key => $format) {
                if ($exclude = $this->config->getOutputFormatProperty($format, 'exclude')) {
                    // ie:
                    //   formats:
                    //     atom:
                    //       [...]
                    //       exclude: [paginated]
                    if (!is_array($exclude)) {
                        $exclude = [$exclude];
                    }
                    foreach ($exclude as $variable) {
                        if ($page->hasVariable($variable)) {
                            unset($formats[$key]);
                        }
                    }
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
                    $output = $this->outputPostProcess($output, $page, $format);
                    $rendered[$format] = [
                        'output'   => $output,
                        'template' => [
                            'scope' => $layout['scope'],
                            'file'  => $layout['file'],
                        ]
                    ];
                    $page->addRendered($rendered);
                    // profiler
                    if ($this->builder->isDebug()) {
                        $dumper = new \Twig\Profiler\Dumper\HtmlDumper();
                        file_put_contents(
                            Util::joinFile($this->config->getOutputPath(), '_debug_twig_profile.html'),
                            $dumper->dump($this->builder->getRenderer()->getDebugProfile())
                        );
                    }
                } catch (\Twig\Error\Error $e) {
                    $template = !empty($e->getSourceContext()->getPath()) ? $e->getSourceContext()->getPath() : $e->getSourceContext()->getName();

                    throw new RuntimeException(\sprintf(
                        'Template "%s%s" (page: %s): %s',
                        $template,
                        $e->getTemplateLine() >= 0 ? \sprintf(':%s', $e->getTemplateLine()) : '',
                        $page->getId(),
                        $e->getMessage()
                    ));
                }
            }
            $this->builder->getPages()->replace($page->getId(), $page);

            $templates = array_column($rendered, 'template');
            $message = \sprintf(
                'Page "%s" rendered with template(s) "%s"',
                ($page->getId() ?: 'index'),
                Util\Str::combineArrayToString($templates, 'scope', 'file')
            );
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
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
            $themes = $this->config->getTheme();
            foreach ($themes as $theme) {
                $paths[] = $this->config->getThemeDirPath($theme);
            }
        }
        // resources/layouts/
        if (is_dir($this->config->getInternalLayoutsPath())) {
            $paths[] = $this->config->getInternalLayoutsPath();
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

        return $formats;
    }

    /**
     * Get alternates.
     */
    protected function getAlternates(array $formats): array
    {
        $alternates = [];

        if (count($formats) > 1 || in_array('html', $formats)) {
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
    protected function getTranslations(Page $refPage): \Cecil\Collection\Page\Collection
    {
        $pages = $this->builder->getPages()->filter(function (Page $page) use ($refPage) {
            return $page->getId() !== $refPage->getId()
                && $page->getVariable('langref') == $refPage->getVariable('langref')
                && $page->getType() == $refPage->getType()
                && !empty($page->getVariable('published'))
                && !$page->getVariable('paginated');
        });

        return $pages;
    }

    /**
     * Apply post processors on rendered output.
     */
    private function outputPostProcess(string $output, Page $page, string $format): string
    {
        switch ($format) {
            case 'html':
                // add generator meta tag
                if (!preg_match('/<meta name="generator".*/i', $output)) {
                    $meta = \sprintf('<meta name="generator" content="Cecil %s" />', Builder::getVersion());
                    $output = preg_replace_callback('/([[:blank:]]*)(<\/head>)/i', function ($matches) use ($meta) {
                        return str_repeat($matches[1] ?: ' ', 2) . $meta . "\n" . $matches[1] . $matches[2];
                    }, $output);
                }
                // replace excerpt or break tag by HTML anchor
                // https://regex101.com/r/Xl7d5I/3
                $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
                $replacement = '$1<span id="more"></span>$4';
                $excerpt = preg_replace('/' . $pattern . '/is', $replacement, $output, 1);
                $output = $excerpt ?? $output;
        }

        // replace internal link to *.md files with the right URL
        $output = preg_replace_callback(
            // https://regex101.com/r/ycWMe4/1
            '/href="(\/|)([A-Za-z0-9_\.\-\/]+)\.md(\#[A-Za-z0-9_\-]+)?"/is',
            function ($matches) use ($page) {
                // section spage
                $hrefPattern = 'href="../%s/%s"';
                // root page
                if (empty($page->getFolder())) {
                    $hrefPattern = 'href="%s/%s"';
                }
                // root link
                if ($matches[1] == '/') {
                    $hrefPattern = 'href="/%s/%s"';
                }

                return \sprintf($hrefPattern, Page::slugify(PrefixSuffix::sub($matches[2])), $matches[3] ?? '');
            },
            $output
        );

        return $output;
    }
}
