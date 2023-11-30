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

namespace Cecil\Step\Menus;

use Cecil\Collection\Menu\Collection as MenusCollection;
use Cecil\Collection\Menu\Entry;
use Cecil\Collection\Menu\Menu;
use Cecil\Collection\Page\Page;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\PrintLogger;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Step\AbstractStep;

/**
 * Creates menus collection.
 */
class Create extends AbstractStep
{
    /** @var array */
    protected $menus;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Creating menus';
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(): void
    {
        // creates a 'menus' collection for each language, with a default 'main' menu
        foreach ($this->config->getLanguages() as $language) {
            $this->menus[$language['code']] = new MenusCollection('menus');
            $this->menus[$language['code']]->add(new Menu('main'));
        }

        // collects 'menu' entries from pages
        $this->collectPages();

        /**
         * Removing/adding/replacing menus entries from config.
         * ie:
         *   menus:
         *     main:
         *       - id: example
         *         name: "Example"
         *         url: https://example.com
         *         weight: 999
         *       - id: about
         *         enabled: false.
         */
        foreach ($this->config->getLanguages() as $language) {
            if ($menusConfig = (array) $this->config->get('menus', $language['code'], false)) {
                $totalConfig = array_sum(array_map('count', $menusConfig));
                $countConfig = 0;
                $langPrefix = '';
                $page404 = '404.html';

                if ($language['code'] !== $this->config->getLanguageDefault()) {
                    $langPrefix = $language['code'] . '/';
                    $page404 = $language['code'] . '/404.html';
                }

                foreach ($menusConfig as $menuConfig => $entry) {
                    // add Menu if not exists
                    if (!$this->menus[$language['code']]->has($menuConfig)) {
                        $this->menus[$language['code']]->add(new Menu($menuConfig));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$language['code']]->get($menuConfig);
                    foreach ($entry as $key => $property) {
                        $countConfig++;
                        $enabled = true;
                        $updated = false;

                        // ID is required
                        if (!isset($property['id'])) {
                            throw new RuntimeException(sprintf('"id" is required for entry at position %s in "%s" menu', $key, $menu));
                        }
                        // enabled?
                        if (isset($property['enabled']) && $property['enabled'] === false) {
                            $enabled = false;
                            if (!$menu->has($property['id'])) {
                                $message = sprintf('Config menu entry "%s > %s%s" disabled', (string) $menu, $langPrefix, $property['id']);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                            }
                        }
                        // is entry already exists?
                        if ($menu->has($property['id'])) {
                            // removes a disabled entry
                            if (!$enabled) {
                                $menu->remove($property['id']);

                                $message = sprintf('Config menu entry "%s > %s%s" removed', (string) $menu, $langPrefix, $property['id']);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                                continue;
                            }
                            // merges properties
                            $updated = true;
                            $current = $menu->get($property['id'])->toArray();
                            $property = array_merge($current, $property);

                            $message = sprintf('Config menu entry "%s > %s%s" updated', (string) $menu, $langPrefix, $property['id']);
                            $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                        }
                        // adds/replaces entry
                        if ($enabled) {
                            $item = (new Entry($property['id']))
                                ->setName($property['name'] ?? ucfirst($property['id']))
                                ->setUrl($property['url'] ?? $page404)
                                ->setWeight((int) ($property['weight'] ?? 0));
                            $menu->add($item);

                            if (!$updated) {
                                $message = sprintf('Config menu entry "%s > %s%s" created', (string) $menu, $langPrefix, $property['id']);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                            }
                        }
                    }
                }
            }
        }

        $this->builder->setMenus($this->menus);
    }

    /**
     * Collects pages with a menu variable.
     */
    protected function collectPages(): void
    {
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->hasVariable('menu')
                && $page->getVariable('published')
                && \in_array($page->getVariable('language', $this->config->getLanguageDefault()), array_column($this->config->getLanguages(), 'code'));
        });

        $total = \count($filteredPages);
        $count = 0;
        /** @var \Cecil\Collection\Page\Page $page */
        foreach ($filteredPages as $page) {
            $count++;
            $language = $page->getVariable('language', $this->config->getLanguageDefault());
            /**
             * Array case.
             *
             * ie 1:
             *   menu: [main, navigation]
             * ie 2:
             *   menu:
             *     main:
             *       weight: 999
             */
            if (\is_array($page->getVariable('menu'))) {
                foreach ($page->getVariable('menu') as $key => $value) {
                    $menuName = $key;
                    $property = $value;
                    $weight = null;
                    if (\is_int($key)) {
                        $menuName = $value;
                        $property = null;
                    }
                    if (!\is_string($menuName)) {
                        $this->builder->getLogger()->error(
                            sprintf(
                                'Menu\'s name of page "%s" must be a string, not "%s"',
                                $page->getId(),
                                PrintLogger::format($menuName)
                            ),
                            ['progress' => [$count, $total]]
                        );
                        continue;
                    }
                    $item = (new Entry($page->getIdWithoutLang()))
                        ->setName($page->getVariable('title'))
                        ->setUrl((new PageRenderer($this->config))->getUrl($page));
                    if (isset($property['weight'])) {
                        $weight = $property['weight'];
                        $item->setWeight((int) $property['weight']);
                    }
                    // add Menu if not exists
                    if (!$this->menus[$language]->has($menuName)) {
                        $this->menus[$language]->add(new Menu($menuName));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$language]->get($menuName);
                    $menu->add($item);

                    $message = sprintf('Page menu entry "%s > %s" created (weight: %s)', $menuName, $page->getId(), $weight ?? 'N/A');
                    $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
                }
                continue;
            }
            /**
             * String case.
             *
             * ie:
             *   menu: main
             */
            $item = (new Entry($page->getIdWithoutLang()))
                ->setName($page->getVariable('title'))
                ->setUrl((new PageRenderer($this->config))->getUrl($page));
            // add Menu if not exists
            if (!$this->menus[$language]->has($page->getVariable('menu'))) {
                $this->menus[$language]->add(new Menu($page->getVariable('menu')));
            }
            /** @var \Cecil\Collection\Menu\Menu $menu */
            $menu = $this->menus[$language]->get($page->getVariable('menu'));
            $menu->add($item);

            $message = sprintf('Page menu entry "%s > %s" created', $page->getVariable('menu'), $page->getId());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
    }
}
