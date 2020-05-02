<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\PrefixSuffix;
use Cecil\Exception\Exception;
use Cecil\Renderer\Layout;
use Cecil\Renderer\Site;
use Cecil\Renderer\Twig;
use Cecil\Util;

/**
 * Pages rendering.
 */
class PagesRender extends AbstractStep
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
     *
     * @throws Exception
     */
    public function init($options)
    {
        if (!is_dir($this->config->getLayoutsPath()) && !$this->config->hasTheme()) {
            $message = sprintf(
                "'%s' is not a valid layouts directory",
                $this->config->getLayoutsPath()
            );
            $this->builder->getLogger()->debug($message);
        }

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function process()
    {
        // prepares renderer
        $this->builder->setRenderer(new Twig($this->getAllLayoutsPaths(), $this->builder));

        // adds global variables
        $this->addGlobals();

        // collects published pages
        /** @var Page $page */
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return !empty($page->getVariable('published'));
        });
        $max = count($filteredPages);

        // renders each page
        $count = 0;
        /** @var Page $page */
        foreach ($filteredPages as $page) {
            $count++;
            $rendered = [];

            // i18n
            $pageLang = $page->getVariable('language');
            $locale = $this->config->getLanguageProperty('locale', $pageLang);
            // the PHP Intl extension is needed to use localized date
            if (extension_loaded('intl')) {
                \Locale::setDefault($locale);
            }
            // the PHP Gettext extension is needed to use translation
            if (extension_loaded('gettext')) {
                $localePath = realpath(Util::joinFile($this->config->getSourceDir(), 'locale'));
                $domain = 'messages';
                putenv("LC_ALL=$locale");
                putenv("LANGUAGE=$locale");
                setlocale(LC_ALL, "$locale.UTF-8");
                bindtextdomain($domain, $localePath);
            }

            // global site variables
            $this->builder->getRenderer()->addGlobal('site', new Site($this->builder, $pageLang));

            // get Page's output formats
            $formats = $this->getOutputFormats($page);
            $page->setVariable('output', $formats);

            // excluded format(s)?
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

            // get and set alternates links
            $page->setVariable('alternates', $this->getAlternates($formats));

            // renders each output format
            foreach ($formats as $format) {
                // search for the template
                $layout = Layout::finder($page, $format, $this->config);
                // renders with Twig
                try {
                    $output = $this->builder->getRenderer()->render($layout['file'], ['page' => $page]);
                    $output = $this->postProcessOutput($output, $page, $format);
                    $rendered[$format]['output'] = $output;
                    $rendered[$format]['template']['scope'] = $layout['scope'];
                    $rendered[$format]['template']['file'] = $layout['file'];
                    // profiler
                    if (getenv('CECIL_DEBUG') == 'true') {
                        $dumper = new \Twig\Profiler\Dumper\HtmlDumper();
                        file_put_contents(
                            Util::joinFile($this->config->getOutputPath(), '_debug_twig_profile.html'),
                            $dumper->dump($this->builder->getRenderer()->profile)
                        );
                    }
                } catch (\Twig\Error\Error $e) {
                    throw new Exception(sprintf(
                        'Template "%s:%s"%s (for page "%s"): %s',
                        $layout['scope'],
                        $layout['file'],
                        $e->getTemplateLine() >= 0 ? sprintf(' line %s', $e->getTemplateLine()) : '',
                        $page->getId(),
                        $e->getMessage()
                    ));
                }
            }
            $page->setVariable('rendered', $rendered);
            $this->builder->getPages()->replace($page->getId(), $page);

            $templates = array_column($rendered, 'template');
            $message = sprintf(
                '%s [%s]',
                ($page->getId() ?: 'index'),
                Util::combineArrayToString($templates, 'scope', 'file')
            );
            $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
        }
    }

    /**
     * Returns an array of layouts directories.
     *
     * @return array
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
        // res/layouts/
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
            'url'       => sprintf('https://cecil.app/#%s', Builder::getVersion()),
            'version'   => Builder::getVersion(),
            'poweredby' => sprintf('Cecil v%s', Builder::getVersion()),
        ]);
    }

    /**
     * Get available output formats.
     *
     * @param Page $page
     *
     * @return array
     */
    protected function getOutputFormats(Page $page): array
    {
        // Get available output formats for the page type.
        // ie:
        //   page: [html, json]
        $formats = $this->config->get('output.pagetypeformats.'.$page->getType());

        if (empty($formats)) {
            throw new Exception('Configuration key "pagetypeformats" can\'t be empty.');
        }

        if (!\is_array($formats)) {
            $formats = [$formats];
        }

        // Get page output format(s).
        // ie:
        //   output: txt
        if ($page->getVariable('output')) {
            $formats = $page->getVariable('output');
            if (!\is_array($formats)) {
                $formats = [$formats];
            }
        }

        return $formats;
    }

    /**
     * Get alternates.
     *
     * @param array $formats
     *
     * @return array
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
     * Apply post rendering on output.
     *
     * @param string $rendered
     * @param Page   $page
     * @param string $format
     *
     * @return string
     */
    private function postProcessOutput(string $rendered, Page $page, string $format): string
    {
        switch ($format) {
            case 'html':
                // add generator meta
                if (!preg_match('/<meta name="generator".*/i', $rendered)) {
                    $meta = \sprintf('<meta name="generator" content="Cecil %s" />', Builder::getVersion());
                    $rendered = preg_replace('/(<\/head>)/i', "\t$meta\n  $1", $rendered);
                }
                // replace excerpt or break tag by HTML anchor
                // https://regex101.com/r/Xl7d5I/3
                $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
                $replacement = '$1<span id="more"></span>$4';
                $rendered = preg_replace('/'.$pattern.'/is', $replacement, $rendered);
        }

        // replace internal link to *.md files with the right URL
        // https://regex101.com/r/dZ02zO/5
        $replace = 'href="../%s/%s"';
        if (empty($page->getFolder())) {
            $replace = 'href="%s/%s"';
        }
        $rendered = preg_replace_callback(
            '/href="([A-Za-z0-9_\.\-\/]+)\.md(\#[A-Za-z0-9\-]+)?"/is',
            function ($matches) use ($replace) {
                return \sprintf($replace, Page::slugify(PrefixSuffix::sub($matches[1])), $matches[2] ?? '');
            },
            $rendered
        );

        return $rendered;
    }
}
