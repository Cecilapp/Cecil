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

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Extension\Core as CoreExtension;
use Cecil\Util;
use Performing\TwigComponents\Configuration;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\Translator;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;

/**
 * Twig renderer.
 *
 * This class is responsible for rendering templates using the Twig templating engine.
 * It initializes Twig with the necessary configurations, loads extensions, and provides methods
 * to render templates, add global variables, and manage translations.
 * It also supports debugging and profiling when in debug mode.
 */
class Twig implements RendererInterface
{
    /**
     * Builder object.
     * @var Builder
     */
    protected $builder;
    /**
     * Twig environment instance.
     * @var \Twig\Environment
     */
    private $twig;
    /**
     * Translator instance for translations.
     * @var Translator
     */
    private $translator = null;
    /**
     * Profile for debugging and profiling.
     * @var \Twig\Profiler\Profile
     */
    private $profile = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder, $templatesPath)
    {
        $this->builder = $builder;
        // load layouts
        $loader = new \Twig\Loader\FilesystemLoader($templatesPath);
        // default options
        $loaderOptions = [
            'debug'            => $this->builder->isDebug(),
            'strict_variables' => true,
            'autoescape'       => false,
            'auto_reload'      => true,
            'cache'            => false,
        ];
        // use Twig cache?
        if ($this->builder->getConfig()->isEnabled('cache.templates')) {
            $loaderOptions = array_replace($loaderOptions, ['cache' => $this->builder->getConfig()->getCacheTemplatesPath()]);
        }
        // create the Twig instance
        $this->twig = new \Twig\Environment($loader, $loaderOptions);
        // set date format
        $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
            ->setDateFormat((string) $this->builder->getConfig()->get('date.format'));
        // set timezone
        if ($this->builder->getConfig()->has('date.timezone')) {
            $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
                ->setTimezone($this->builder->getConfig()->get('date.timezone') ?? date_default_timezone_get());
        }
        /*
         * adds extensions
         */
        // Cecil core extension
        $this->twig->addExtension(new CoreExtension($this->builder));
        // required by `template_from_string()`
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        // the `u` filter (https://github.com/twigphp/string-extra)
        $this->twig->addExtension(new StringExtension());
        // l10n
        $this->translator = new Translator(
            $this->builder->getConfig()->getLanguageProperty('locale'),
            new MessageFormatter(new IdentityTranslator()),
            $this->builder->getConfig()->isEnabled('cache.translations') ? $this->builder->getConfig()->getCacheTranslationsPath() : null,
            $this->builder->isDebug()
        );
        if (\count($this->builder->getConfig()->getLanguages()) > 0) {
            foreach ((array) $this->builder->getConfig()->get('layouts.translations.formats') as $format) {
                $loader = \sprintf('Symfony\Component\Translation\Loader\%sFileLoader', ucfirst($format));
                if (class_exists($loader)) {
                    $this->translator->addLoader($format, new $loader());
                    $this->builder->getLogger()->debug(\sprintf('Translation loader for format "%s" found', $format));
                }
            }
            foreach ($this->builder->getConfig()->getLanguages() as $lang) {
                // internal
                $this->addTransResource($this->builder->getConfig()->getTranslationsInternalPath(), $lang['locale']);
                // themes
                if ($themes = $this->builder->getConfig()->getTheme()) {
                    foreach ($themes as $theme) {
                        $this->addTransResource($this->builder->getConfig()->getThemeDirPath($theme, 'translations'), $lang['locale']);
                    }
                }
                // site
                $this->addTransResource($this->builder->getConfig()->getTranslationsPath(), $lang['locale']);
            }
        }
        $this->twig->addExtension(new TranslationExtension($this->translator));
        // intl
        $this->twig->addExtension(new IntlExtension());
        if (\extension_loaded('intl')) {
            $this->builder->getLogger()->debug('PHP Intl extension is loaded');
        }
        // filters fallback
        $this->twig->registerUndefinedFilterCallback(function ($name) {
            switch ($name) {
                case 'localizeddate':
                    return new \Twig\TwigFilter($name, function (?\DateTime $value = null) {
                        return date($this->builder->getConfig()->get('date.format'), $value->getTimestamp());
                    });
            }

            return false;
        });
        // components
        Configuration::make($this->twig)
            ->setTemplatesPath($this->builder->getConfig()->get('layouts.components.dir') ?? 'components')
            ->setTemplatesExtension($this->builder->getConfig()->get('layouts.components.ext') ?? 'twig')
            ->useCustomTags()
            ->setup();
        // cache
        $this->twig->addExtension(new CacheExtension());
        $this->twig->addRuntimeLoader(new TwigCacheRuntimeLoader($this->builder->getConfig()->getCacheTemplatesPath()));
        // debug
        if ($this->builder->isDebug()) {
            // dump()
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
            // profiler
            $this->profile = new \Twig\Profiler\Profile();
            $this->twig->addExtension(new \Twig\Extension\ProfilerExtension($this->profile));
        }
        // loads custom extensions
        if ($this->builder->getConfig()->has('layouts.extensions')) {
            foreach ((array) $this->builder->getConfig()->get('layouts.extensions') as $name => $class) {
                try {
                    $this->twig->addExtension(new $class($this->builder));
                    $this->builder->getLogger()->debug(\sprintf('Twig extension "%s" added', $name));
                } catch (RuntimeException | \Error $e) {
                    $this->builder->getLogger()->error(\sprintf('Unable to add Twig extension "%s": %s', $name, $e->getMessage()));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal(string $name, $value): void
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $variables): string
    {
        return $this->twig->render($template, $variables);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): void
    {
        if (\extension_loaded('intl')) {
            \Locale::setDefault($locale);
        }
        $this->translator === null ?: $this->translator->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function addTransResource(string $translationsDir, string $locale): void
    {
        $locales = [$locale];
        // if locale is 'fr_FR', trying to load ['fr', 'fr_FR']
        if (\strlen($locale) > 2) {
            array_unshift($locales, substr($locale, 0, 2));
        }
        foreach ($locales as $locale) {
            foreach ((array) $this->builder->getConfig()->get('layouts.translations.formats') as $format) {
                $translationFile = Util::joinPath($translationsDir, \sprintf('messages.%s.%s', $locale, $format));
                if (Util\File::getFS()->exists($translationFile)) {
                    $this->translator->addResource($format, $translationFile, $locale);
                    $this->builder->getLogger()->debug(\sprintf('Translation file "%s" added', $translationFile));
                }
            }
        }
    }

    /**
     * Returns the Twig instance.
     */
    public function getTwig(): \Twig\Environment
    {
        return $this->twig;
    }

    /**
     * Returns debug profile.
     */
    public function getDebugProfile(): ?\Twig\Profiler\Profile
    {
        return $this->profile;
    }
}
