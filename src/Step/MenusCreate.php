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
        // create menus collection, with a 'main' menu
        $main = new Menu('main');
        $this->menus = new MenusCollection('menus');
        $this->menus->add($main);

        // add entries from pages to menus collection
        $this->collectPages();

        /*
         * Removing/adding/replacing menus entries from config
         * ie:
         *   site:
         *     menu:
         *       main:
         *         example:
         *           name: "Example"
         *           url: https://example.com
         *           weight: 999
         *         a-propos:
         *           id: about
         *           enabled: false
         */
        if ($menus = $this->builder->getConfig()->get('site.menu')) {
            call_user_func_array($this->builder->getMessageCb(), ['MENU', 'Creating menus (config)']);
            $totalConfig = array_sum(array_map('count', $menus));
            $countConfig = 0;
            foreach ($menus as $menu => $entry) {
                /* @var $menu \Cecil\Collection\Menu\Menu */
                if (!$this->menus->has($menu)) {
                    $this->menus->add(new Menu($menu));
                }
                $menu = $this->menus->get($menu);
                foreach ($entry as $key => $property) {
                    $countConfig++;
                    $enabled = true;
                    $updated = false;

                    // ID is key
                    if (!array_key_exists('id', $property)) {
                        $property['id'] = $key;
                    }
                    // enabled?
                    if (array_key_exists('enabled', $property) && false === $property['enabled']) {
                        $enabled = false;
                        if (!$menu->has($property['id'])) {
                            call_user_func_array($this->builder->getMessageCb(), [
                                'MENU_PROGRESS',
                                sprintf('%s > %s (disabled)', $menu, $property['id']),
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
                            ->setName($property['name'] ?? ucfirst($key))
                            ->setUrl($property['url'] ?? '/noURL')
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

        /* @var $page \Cecil\Collection\Page\Page */
        foreach ($filteredPages as $page) {
            $count++;
            /* @var $menu \Cecil\Collection\Menu\Menu */
            $menu = $page->getVariable('menu');

            /*
             * Single case
             * ie:
             *   menu: main
             */
            if (is_string($page->getVariable('menu'))) {
                $item = (new Entry($page->getId()))
                    ->setName($page->getVariable('title'))
                    ->setUrl($page->getUrl());
                if (!$this->menus->has($menu)) {
                    $this->menus->add(new Menu($menu));
                }
                $this->menus->get($menu)->add($item);
            } else {
                /*
                 * Multiple case
                 * ie:
                 *   menu:
                 *     main:
                 *       weight: 999
                 *     other
                 */
                if (is_array($menu)) {
                    foreach ($menu as $menu => $property) {
                        $item = (new Entry($page->getId()))
                            ->setName($page->getVariable('title'))
                            ->setUrl($page->getId())
                            ->setWeight($property['weight']);
                        /* @var $menu \Cecil\Collection\Menu\Menu */
                        if (!$this->menus->has($menu)) {
                            $this->menus->add(new Menu($menu));
                        }
                        $this->menus->get($menu)->add($item);
                    }
                }
            }
            call_user_func_array($this->builder->getMessageCb(), [
                'MENU_PROGRESS',
                sprintf('%s > %s', $menu, $page->getId()),
                $count,
                $total,
            ]);
        }
    }
}
