<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Collection\Menu\Collection as MenusCollection;
use PHPoole\Collection\Menu\Entry;

/**
 * Generates menus.
 */
class GenerateMenus extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->phpoole->getMessageCb(), ['MENU', 'Generating menus']);
        $count = 0;
        $this->phpoole->setMenus(new MenusCollection());
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
        if (!empty($this->phpoole->getConfig()->get('site.menu'))) {
            foreach ($this->phpoole->getConfig()->get('site.menu') as $name => $entry) {
                /* @var $menu \PHPoole\Collection\Menu\Menu */
                $menu = $this->phpoole->getMenus()->get($name);
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
                        ->setWeight($property['weight']);
                    $menu->add($item);
                    $count++;
                }
            }
        }
        if ($count) {
            call_user_func_array($this->phpoole->getMessageCb(), ['MENU_PROGRESS', 'Start generating', 0, $count]);
            call_user_func_array($this->phpoole->getMessageCb(), ['MENU_PROGRESS', 'Menus generated', $count, $count]);
        } else {
            call_user_func_array($this->phpoole->getMessageCb(), ['MENU_PROGRESS', 'No menu']);
        }
    }

    /**
     * Collects pages with menu entry.
     */
    protected function collectPages()
    {
        foreach ($this->phpoole->getPages() as $page) {
            /* @var $page \PHPoole\Collection\Page\Page */
            if (!empty($page['menu'])) {
                /*
                 * Single case
                 * ie:
                 * menu: main
                 */
                if (is_string($page['menu'])) {
                    $item = (new Entry($page->getId()))
                        ->setName($page->getTitle())
                        ->setUrl($page->getPermalink());
                    /* @var $menu \PHPoole\Collection\Menu\Menu */
                    $menu = $this->phpoole->getMenus()->get($page['menu']);
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
                                ->setName($page->getTitle())
                                ->setUrl($page->getPermalink())
                                ->setWeight($value['weight']);
                            /* @var $menu \PHPoole\Collection\Menu\Menu */
                            $menu = $this->phpoole->getMenus()->get($name);
                            $menu->add($item);
                        }
                    }
                }
            }
        }
    }
}
