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

use Cecil\Collection\Menu\Collection as MenusCollection;
use Cecil\Collection\Menu\Entry;
use Cecil\Collection\Menu\Menu;
use Cecil\Collection\Page\Page;
use Cecil\Exception\Exception;
use Cecil\Logger\PrintLogger;

/**
 * Creates menus collection.
 */
class MenusCreate extends AbstractStep
{
    /**
     * @var array
     */
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
     */
    public function process()
    {
        // creates a 'menus' collection for each language, with a default 'main' menu
        foreach ($this->config->getLanguages() as $language) {
            $lang = $language['code'] == $this->config->getLanguageDefault() ? null : $language['code'];
            $this->menus[$lang] = new MenusCollection('menus');
            $this->menus[$lang]->add(new Menu('main'));
        }

        // collects 'menu' entries from pages
        $this->collectPages();

        /**
         * Removing/adding/replacing menus entries from config
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
            if ($menusConfig = $this->builder->getConfig()->get('menus', $language['code'], false)) {
                $this->builder->getLogger()->debug('Creating config menus');

                $totalConfig = array_sum(array_map('count', $menusConfig));
                $countConfig = 0;
                $lang = $language['code'] == $this->config->getLanguageDefault() ? null : $language['code'];

                foreach ($menusConfig as $menuConfig => $entry) {
                    if (!$this->menus[$lang]->has($menuConfig)) {
                        $this->menus[$lang]->add(new Menu($menuConfig));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$lang]->get($menuConfig);
                    foreach ($entry as $key => $property) {
                        $countConfig++;
                        $enabled = true;
                        $updated = false;

                        // ID is required
                        if (!array_key_exists('id', $property)) {
                            throw new Exception(sprintf('"id" is required for menu entry "%s"', $key));
                        }
                        // enabled?
                        if (array_key_exists('enabled', $property) && false === $property['enabled']) {
                            $enabled = false;
                            if (!$menu->has($property['id'])) {
                                $message = sprintf('%s > %s (disabled)', (string) $menu, $property['id']);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                            }
                        }
                        // is entry already exists?
                        if ($menu->has($property['id'])) {
                            // removes a disabled entry
                            if (!$enabled) {
                                $menu->remove($property['id']);

                                $message = sprintf('%s > %s (removed)', $menu, $property['id']);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);

                                continue;
                            }
                            // merges properties
                            $updated = true;
                            $current = $menu->get($property['id'])->toArray();
                            $property = array_merge($current, $property);

                            $message = sprintf('%s > %s (updated)', $menu, $property['id']);
                            $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                        }
                        // adds/replaces entry
                        if ($enabled) {
                            $item = (new Entry($property['id']))
                                ->setName($property['name'] ?? ucfirst($property['id']))
                                ->setUrl($property['url'] ?? '/404')
                                ->setWeight($property['weight'] ?? 0);
                            $menu->add($item);

                            if (!$updated) {
                                $message = sprintf('%s > %s', $menu, $property['id']);
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
    protected function collectPages()
    {
        $count = 0;

        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->hasVariable('menu') && $page->getVariable('published');
        });

        $total = count($filteredPages);

        if ($total > 0) {
            $this->builder->getLogger()->debug('Creating pages menus');
        }

        /** @var \Cecil\Collection\Page\Page $page */
        foreach ($filteredPages as $page) {
            $count++;
            $lang = $page->getVariable('language');
            /**
             * Array case.
             *
             * ie 1:
             *   menu:
             *     main:
             *       weight: 999
             * ie 2:
             *   menu: [main, navigation]
             */
            if (is_array($page->getVariable('menu'))) {
                foreach ($page->getVariable('menu') as $key => $value) {
                    $menuName = $key;
                    $property = $value;
                    $weight = null;
                    if (is_int($key)) {
                        $menuName = $value;
                        $property = null;
                    }
                    if (!is_string($menuName)) {
                        $this->builder->getLogger()->error(
                            sprintf(
                                'Menu name of page "%s" must be string, not "%s"',
                                $page->getId(),
                                PrintLogger::format($menuName)
                            ),
                            ['progress' => [$count, $total]]
                        );
                        continue;
                    }
                    $item = (new Entry($page->getIdWithoutLang()))
                        ->setName($page->getVariable('title'))
                        ->setUrl($page->getId());
                    if (array_key_exists('weight', (array) $property)) {
                        $weight = $property['weight'];
                        $item->setWeight($property['weight']);
                    }
                    // checks if page's language exists
                    if (!array_key_exists($lang, $this->menus)) {
                        if ($lang === null) {
                            throw new Exception('The default language is not correctly defined in config.');
                        }
                        throw new Exception(sprintf('Language "%s" is not defined in config.', $lang));
                    }
                    // add Menu if not exists
                    if (!$this->menus[$lang]->has($menuName)) {
                        $this->menus[$lang]->add(new Menu($menuName));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$lang]->get($menuName);
                    $menu->add($item);

                    $message = sprintf('%s > %s (weight: %s)', $menuName, $page->getId(), $weight ?? 'N/A');
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
            $item = (new Entry($page->getId()))
                ->setName($page->getVariable('title'))
                ->setUrl($page->getUrl());
            if (!$this->menus[$lang]->has($page->getVariable('menu'))) {
                $this->menus[$lang]->add(new Menu($page->getVariable('menu')));
            }
            /** @var \Cecil\Collection\Menu\Menu $menu */
            $menu = $this->menus[$lang]->get($page->getVariable('menu'));
            $menu->add($item);

            $message = sprintf('%s > %s', $page->getVariable('menu'), $page->getId());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
    }
}
