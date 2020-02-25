<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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

/**
 * Create menus collection.
 */
class MenusCreate extends AbstractStep
{
    /**
     * @var MenusCollection
     */
    protected $menus;

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // create the 'menus' collection with a default 'main' menu
        $main = new Menu('main');
        $this->menus = new MenusCollection('menus');
        $this->menus->add($main);

        // Collect 'menu' entries from pages
        $this->collectPages();

        /*
         * Removing/adding/replacing menus entries from config
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
        if ($menusConfig = $this->builder->getConfig()->get('menus')) {
            call_user_func_array($this->builder->getMessageCb(), ['MENU', 'Creating menus (config)']);
            $totalConfig = array_sum(array_map('count', $menusConfig));
            $countConfig = 0;

            foreach ($menusConfig as $menuConfig => $entry) {
                if (!$this->menus->has($menuConfig)) {
                    $this->menus->add(new Menu($menuConfig));
                }
                /** @var \Cecil\Collection\Menu\Menu $menu */
                $menu = $this->menus->get($menuConfig);
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
                            call_user_func_array($this->builder->getMessageCb(), [
                                'MENU_PROGRESS',
                                sprintf('%s > %s (disabled)', (string) $menu, $property['id']),
                                $countConfig,
                                $totalConfig,
                            ]);
                        }
                    }
                    // is entry already exist?
                    if ($menu->has($property['id'])) {
                        // remove a disabled entry
                        if (!$enabled) {
                            call_user_func_array($this->builder->getMessageCb(), [
                                'MENU_PROGRESS',
                                sprintf('%s > %s (removed)', $menu, $property['id']),
                                $countConfig,
                                $totalConfig,
                            ]);
                            $menu->remove($property['id']);
                            continue;
                        }
                        // merge properties
                        $updated = true;
                        $current = $menu->get($property['id'])->toArray();
                        $property = array_merge($current, $property);
                        call_user_func_array($this->builder->getMessageCb(), [
                            'MENU_PROGRESS',
                            sprintf('%s > %s (updated)', $menu, $property['id']),
                            $countConfig,
                            $totalConfig,
                        ]);
                    }
                    // add/replace entry
                    if ($enabled) {
                        $item = (new Entry($property['id']))
                            ->setName($property['name'] ?? ucfirst($property['id']))
                            ->setUrl($property['url'] ?? '/404')
                            ->setWeight($property['weight'] ?? 0);
                        $menu->add($item);
                        if (!$updated) {
                            call_user_func_array($this->builder->getMessageCb(), [
                                'MENU_PROGRESS',
                                sprintf('%s > %s', $menu, $property['id']),
                                $countConfig,
                                $totalConfig,
                            ]);
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

        $filteredPages = $this->builder->getPages()
            ->filter(function (Page $page) {
                if ($page->getVariable('menu')) {
                    return true;
                }
            });

        $total = count($filteredPages);

        if ($total > 0) {
            call_user_func_array($this->builder->getMessageCb(), ['MENU', 'Creating menus (pages)']);
        }

        /** @var \Cecil\Collection\Page\Page $page */
        foreach ($filteredPages as $page) {
            $count++;
            $menuFromPage = $page->getVariable('menu');

            /*
             * Single case
             * ie:
             *   menu: main
             */
            if (is_string($page->getVariable('menu'))) {
                $item = (new Entry($page->getId()))
                    ->setName($page->getVariable('title'))
                    ->setUrl($page->getUrl());
                if (!$this->menus->has($menuFromPage)) {
                    $this->menus->add(new Menu($menuFromPage));
                }
                /** @var \Cecil\Collection\Menu\Menu $menu */
                $menu = $this->menus->get($menuFromPage);
                $menu->add($item);
                // message
                call_user_func_array($this->builder->getMessageCb(), [
                    'MENU_PROGRESS',
                    sprintf('%s > %s', $menuFromPage, $page->getId()),
                    $count,
                    $total,
                ]);
            } else {
                /*
                 * Multiple case
                 * ie:
                 *   menu:
                 *     main:
                 *       weight: 999
                 *     other
                 */
                if (is_array($menuFromPage)) {
                    foreach ($menuFromPage as $menuName => $property) {
                        $item = (new Entry($page->getId()))
                            ->setName($page->getVariable('title'))
                            ->setUrl($page->getId())
                            ->setWeight($property['weight']);
                        if (!$this->menus->has($menuName)) {
                            $this->menus->add(new Menu($menuName));
                        }
                        /** @var \Cecil\Collection\Menu\Menu $menu */
                        $menu = $this->menus->get($menuName);
                        $menu->add($item);
                        // message
                        call_user_func_array($this->builder->getMessageCb(), [
                            'MENU_PROGRESS',
                            sprintf('%s > %s', $menuName, $page->getId()),
                            $count,
                            $total,
                        ]);
                    }

                }
            }
        }
    }
}
