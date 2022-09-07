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
use Cecil\Renderer\Twig\Extension as TwigExtension;
use Cecil\Util;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Extra\Intl\IntlExtension;

/**
 * Class Twig.
 */
class Twig implements RendererInterface
{
    /** @var \Twig\Profiler\Profile */
    public $profile;

    /** @var \Twig\Environment */
    private $twig;

    /** @var Translator */
    private $translator;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder, $templatesPath)
    {
        // load layouts
        $loader = new \Twig\Loader\FilesystemLoader($templatesPath);
        // default options
        $loaderOptions = [
            'debug'            => $builder->isDebug(),
            'strict_variables' => true,
            'autoescape'       => false,
            'auto_reload'      => true,
            'cache'            => false,
        ];
        // use Twig cache?
        if ($builder->getConfig()->get('cache.templates.enabled')) {
            $templatesCachePath = \Cecil\Util::joinFile(
                $builder->getConfig()->getCachePath(),
                (string) $builder->getConfig()->get('cache.templates.dir')
            );
            $loaderOptions = array_replace($loaderOptions, ['cache' => $templatesCachePath]);
        }
        // create the Twig instance
        $this->twig = new \Twig\Environment($loader, $loaderOptions);
        // set date format
        $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
            ->setDateFormat($builder->getConfig()->get('date.format'));
        // set timezone
        if ($builder->getConfig()->has('date.timezone')) {
            $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
                ->setTimezone($builder->getConfig()->get('date.timezone'));
        }
        // adds extensions
        $this->twig->addExtension(new TwigExtension($builder));
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        // i18n
        $this->twig->addExtension(new IntlExtension());
        // l10n
        $locale = $builder->getConfig()->getLanguageProperty('locale');
        $this->translator = new Translator($locale, new MessageFormatter(new IdentityTranslator()));
        $this->translator->setFallbackLocales([$locale]);
        $this->translator->addLoader('mo', new MoFileLoader());
        if (count($builder->getConfig()->getLanguages()) > 1) {
            foreach ($builder->getConfig()->getLanguages() as $lang) {
                $translationFile = realpath(Util::joinFile($builder->getConfig()->getSourceDir(), \sprintf('translations/messages.%s.mo', $lang['locale'])));
                if (Util\File::getFS()->exists($translationFile)) {
                    $this->translator->addResource('mo', $translationFile, $lang['locale']);
                }
            }
        }
        $this->twig->addExtension(new TranslationExtension($this->translator));
        // filters fallback
        $this->twig->registerUndefinedFilterCallback(function ($name) use ($builder) {
            switch ($name) {
                case 'localizeddate':
                    return new \Twig\TwigFilter($name, function (\DateTime $value = null) use ($builder) {
                        return date((string) $builder->getConfig()->get('date.format'), $value->getTimestamp());
                    });
            }

            return false;
        });
        // debug
        if ($builder->isDebug()) {
            // dump()
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
            // profiler
            $this->profile = new \Twig\Profiler\Profile();
            $this->twig->addExtension(new \Twig\Extension\ProfilerExtension($this->profile));
        }
        /**
         * Backward compatibility.
         */
        /*if (extension_loaded('intl')) {
            $this->twig->addExtension(new \Twig\Extensions\IntlExtension());
            $builder->getLogger()->debug('Intl extension is loaded');
        }
        if (extension_loaded('gettext')) {
            $this->twig->addExtension(new \Twig\Extensions\I18nExtension());
            $builder->getLogger()->debug('Gettext extension is loaded');
        }*/
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
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
}
