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

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Renderer\Extension\Core as CoreExtension;
use Cecil\Util;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\Translator;
use Twig\Extra\Intl\IntlExtension;

/**
 * Class Twig.
 */
class Twig implements RendererInterface
{
    /** @var Builder */
    private $builder;

    /** @var \Twig\Environment */
    private $twig;

    /** @var Translator */
    private $translator = null;

    /** @var \Twig\Profiler\Profile */
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
        if ((bool) $this->builder->getConfig()->get('cache.templates.enabled')) {
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
                ->setTimezone((string) $this->builder->getConfig()->get('date.timezone'));
        }
        /*
         * adds extensions
         */
        // Cecil core extension
        $this->twig->addExtension(new CoreExtension($this->builder));
        // required by `template_from_string()`
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        // l10n
        $this->translator = new Translator(
            $this->builder->getConfig()->getLanguageProperty('locale'),
            new MessageFormatter(new IdentityTranslator()),
            (bool) $this->builder->getConfig()->get('cache.templates.enabled') ? $this->builder->getConfig()->getCacheTranslationsPath() : null,
            $this->builder->isDebug()
        );
        if (count($this->builder->getConfig()->getLanguages()) > 0) {
            foreach ((array) $this->builder->getConfig()->get('translations.formats') as $format) {
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
        if (extension_loaded('intl')) {
            $this->builder->getLogger()->debug('Intl extension is loaded');
        }
        // filters fallback
        $this->twig->registerUndefinedFilterCallback(function ($name) {
            switch ($name) {
                case 'localizeddate':
                    return new \Twig\TwigFilter($name, function (\DateTime $value = null) {
                        return $value === null ?: date((string) $this->builder->getConfig()->get('date.format'), $value->getTimestamp());
                    });
            }

            return false;
        });
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
            Util::autoload($builder, 'extensions');
            foreach ((array) $this->builder->getConfig()->get('layouts.extensions') as $name => $class) {
                $this->twig->addExtension(new $class($this->builder));
                $this->builder->getLogger()->debug(\sprintf('Extension "%s" (%s) added.', $name, $class));
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
        if (extension_loaded('intl')) {
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
        if (strlen($locale) > 2) {
            array_unshift($locales, substr($locale, 0, 2));
        }
        foreach ($locales as $locale) {
            foreach ((array) $this->builder->getConfig()->get('translations.formats') as $format) {
                $translationFile = Util::joinPath($translationsDir, \sprintf('messages.%s.%s', $locale, $format));
                if (Util\File::getFS()->exists($translationFile)) {
                    $this->translator->addResource($format, $translationFile, $locale);
                    $this->builder->getLogger()->debug(\sprintf('Translation file "%s" added', $translationFile));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugProfile(): ?\Twig\Profiler\Profile
    {
        return $this->profile;
    }
}
