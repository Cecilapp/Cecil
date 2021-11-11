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
use Cecil\Renderer\Page as PageRenderer;

/**
 * Creates menus collection.
 */
class MenusCreate extends AbstractStep
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
     */
    public function process()
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
         *         enabled: false
         */
        foreach ($this->config->getLanguages() as $language) {
            if ($menusConfig = $this->config->get('menus', $language['code'], false)) {
                $this->builder->getLogger()->debug('Creating menus from config');

                $totalConfig = array_sum(array_map('count', $menusConfig));
                $countConfig = 0;
                $suffix = $language['code'] !== $this->config->getLanguageDefault() ? "." . $language['code'] : '';

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
                        if (!array_key_exists('id', $property)) {
                            throw new Exception(sprintf('"id" is required for entry at position %s in "%s" menu', $key, $menu));
                        }
                        // enabled?
                        if (array_key_exists('enabled', $property) && false === $property['enabled']) {
                            $enabled = false;
                            if (!$menu->has($property['id'])) {
                                $message = sprintf('%s > %s%s (disabled)', (string) $menu, $property['id'], $suffix);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                            }
                        }
                        // is entry already exists?
                        if ($menu->has($property['id'])) {
                            // removes a disabled entry
                            if (!$enabled) {
                                $menu->remove($property['id']);

                                $message = sprintf('%s > %s%s (removed)', (string) $menu, $property['id'], $suffix);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                                continue;
                            }
                            // merges properties
                            $updated = true;
                            $current = $menu->get($property['id'])->toArray();
                            $property = array_merge($current, $property);

                            $message = sprintf('%s > %s%s (updated)', (string) $menu, $property['id'], $suffix);
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
                                $message = sprintf('%s > %s%s', (string) $menu, $property['id'], $suffix);
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
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->hasVariable('menu') && $page->getVariable('published');
        });

        $total = count($filteredPages);
        $count = 0;

        if ($total > 0) {
            $this->builder->getLogger()->debug('Creating menus from pages');
        }

        /** @var \Cecil\Collection\Page\Page $page */
        foreach ($filteredPages as $page) {
            $count++;
            $language = $page->getVariable('language') ?? $this->config->getLanguageDefault();
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
                    if (array_key_exists('weight', (array) $property)) {
                        $weight = $property['weight'];
                        $item->setWeight($property['weight']);
                    }
                    // add Menu if not exists
                    if (!$this->menus[$language]->has($menuName)) {
                        $this->menus[$language]->add(new Menu($menuName));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$language]->get($menuName);
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

            $message = sprintf('%s > %s', $page->getVariable('menu'), $page->getId());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
    }
}
