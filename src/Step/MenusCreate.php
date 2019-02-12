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

/**
 * Generates menus.
 */
class MenusCreate extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['MENU', 'Generating menus']);
        $count = 0;
        $this->builder->setMenus(new MenusCollection());
        $this->collectPages();

        /*
         * Removing/adding/replacing menus entries from config array
         * ie:
         * ['site' => [
         *     'menu' => [
         *         'main' => [
         *             'test' => [
         *                 'id'     => 'test',
         *                 'name'   => 'Test website',
         *                 'url'    => 'http://test.org',
         *                 'weight' => 999,
         *             ],
         *         ],
         *     ],
         * ]]
         */
        if (!empty($this->builder->getConfig()->get('site.menu'))) {
            foreach ($this->builder->getConfig()->get('site.menu') as $name => $entry) {
                /* @var $menu \Cecil\Collection\Menu\Menu */
                $menu = $this->builder->getMenus()->get($name);
                foreach ($entry as $property) {
                    // remove disable entries
                    if (isset($property['disabled']) && $property['disabled']) {
                        if (isset($property['id']) && $menu->has($property['id'])) {
                            $menu->remove($property['id']);
                        }
                        continue;
                    }
                    // add new entries
                    $item = (new Entry($property['id']))
                        ->setName($property['name'])
                        ->setUrl($property['url'])
                        ->setWeight('weight', $property['weight']);
                    $menu->add($item);
                    $count++;
                }
            }
        }
        if ($count) {
            call_user_func_array($this->builder->getMessageCb(), ['MENU_PROGRESS', 'Start generating', 0, $count]);
            call_user_func_array($this->builder->getMessageCb(), ['MENU_PROGRESS', 'Menus generated', $count, $count]);
        } else {
            call_user_func_array($this->builder->getMessageCb(), ['MENU_PROGRESS', 'No menu']);
        }
    }

    /**
     * Collects pages with menu entry.
     */
    protected function collectPages()
    {
        foreach ($this->builder->getPages() as $page) {
            /* @var $page \Cecil\Collection\Page\Page */
            if (!empty($page['menu'])) {
                /*
                 * Single case
                 * ie:
                 * menu: main
                 */
                if (is_string($page['menu'])) {
                    $item = (new Entry($page->getId()))
                        ->setName($page->getVariable('title'))
                        ->setUrl($page->getUrl());
                    /* @var $menu \Cecil\Collection\Menu\Menu */
                    $menu = $this->builder->getMenus()->get($page['menu']);
                    $menu->add($item);
                } else {
                    /*
                     * Multiple case
                     * ie:
                     * menu:
                     *     main:
                     *         weight: 1000
                     *     other
                     */
                    if (is_array($page['menu'])) {
                        foreach ($page['menu'] as $name => $value) {
                            $item = (new Entry($page->getId()))
                                ->setName($page->getVariable('title'))
                                ->setUrl($page->getUrl())
                                ->setWeight('weight', $value['weight']);
                            /* @var $menu \Cecil\Collection\Menu\Menu */
                            $menu = $this->builder->getMenus()->get($name);
                            $menu->add($item);
                        }
                    }
                }
            }
        }
    }
}
