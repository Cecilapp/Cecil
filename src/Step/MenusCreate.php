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

/**
 * Generates menus.
 */
class MenusCreate extends AbstractStep
{
    /**
     * @var string
     */
    protected $menus;

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['MENU', 'Creating menus']);
        $count = 0;

        // create menus collection, with a 'main' menu
        $main = new Menu('pouet');
        $this->menus = new MenusCollection('menus');
        $this->menus->add($main);

        // add entries from pages to menus collection
        call_user_func_array($this->builder->getMessageCb(), ['MENU_PROGRESS', 'Collecting entries']);
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
        if ($this->builder->getConfig()->get('site.menu')) {
            foreach ($this->builder->getConfig()->get('site.menu') as $menu => $entry) {
                /* @var $menu \Cecil\Collection\Menu\Menu */
                $menu = $this->menus->get($menu);
                foreach ($entry as $key => $property) {
                    // ID is key
                    if (!array_key_exists('id', $property)) {
                        $property['id'] = $key;
                    }
                    // is entry already exist?
                    if ($menu->has($property['id'])) {
                        // remove a disabled entry
                        if (array_key_exists('enabled', $property) && false === $property['enabled']) {
                            $menu->remove($property['id']);
                            continue;
                        }
                        // merger properties
                        $current = $menu->get($property['id'])->toArray();
                        $property = array_merge($current, $property);
                    }
                    // add a new entry
                    $item = (new Entry($property['id']))
                        ->setName($property['name'] ?? ucfirst($key))
                        ->setUrl($property['url'] ?? '/noURL')
                        ->setWeight($property['weight'] ?? 0);
                    $menu->add($item);
                    $count++;
                }
            }
        }
        if ($count) {
            call_user_func_array($this->builder->getMessageCb(), ['MENU_PROGRESS', 'Entries created', $count, $count]);
        } else {
            call_user_func_array($this->builder->getMessageCb(), ['MENU_PROGRESS', 'No menu']);
        }

        $this->builder->setMenus($this->menus);
    }

    /**
     * Collects pages with a menu variable.
     */
    protected function collectPages()
    {
        foreach ($this->builder->getPages() as $page) {
            /* @var $page \Cecil\Collection\Page\Page */
            if (!empty($page->getVariable('menu'))) {
                /*
                 * Single case
                 * ie:
                 *   menu: main
                 */
                if (is_string($page->getVariable('menu'))) {
                    $item = (new Entry($page->getId()))
                        ->setName($page->getVariable('title'))
                        ->setUrl($page->getUrl());
                    /* @var $menu \Cecil\Collection\Menu\Menu */
                    $menu = $page->getVariable('menu');
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
                    if (is_array($page->getVariable('menu'))) {
                        foreach ($page->getVariable('menu') as $menu => $property) {
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
            }
        }
    }
}
