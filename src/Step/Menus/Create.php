<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
 * Create menus step.
 *
 * This step is responsible for creating menus based on the configuration
 * and the pages defined in the site. It initializes a collection of menus
 * for each language, adds a default "main" menu, and processes the configuration
 * to add, remove, or replace menu entries. It also creates menus from pages
 * that have a `menu` variable defined, allowing for dynamic menu generation
 * based on the content of the site.
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
        // creates a Menu collection for each language, with a default "main" menu
        foreach ($this->config->getLanguages() as $language) {
            $this->menus[$language['code']] = new MenusCollection('menus');
            $this->menus[$language['code']]->add(new Menu('main'));
        }

        $this->createMenusFromPages();

        /**
         * Removing/adding/replacing menus entries from config.
         * ie:
         *   menus:
         *     main:
         *       # remove
         *       - id: about
         *         enabled: false
         *       # add
         *       - id: example
         *         name: "Example"
         *         url: https://example.com
         *         weight: 999
         *       # replace
         *       - id: index
         *         name: "Home page"
         */
        foreach ($this->config->getLanguages() as $language) {
            if ($menusConfig = (array) $this->config->get('menus', $language['code'], false)) {
                $totalConfig = array_sum(array_map('count', $menusConfig));
                $countConfig = 0;

                foreach ($menusConfig as $menuConfig => $entry) {
                    if (!\is_array($entry)) {
                        break;
                    }
                    // add Menu if not exists
                    if (!$this->menus[$language['code']]->has($menuConfig)) {
                        $this->menus[$language['code']]->add(new Menu($menuConfig));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$language['code']]->get($menuConfig);
                    foreach ($entry as $key => $properties) {
                        $countConfig++;
                        $updated = false;

                        // ID is required
                        if (!isset($properties['id'])) {
                            $this->builder->getLogger()->error(\sprintf('Config menu entry: key "id" is required for entry at position %s in "%s" menu', $key, $menu), ['progress' => [$countConfig, $totalConfig]]);
                            continue;
                        }

                        // Resolve the page matching the config ID (handles sub-sections and localized pages)
                        $page = $this->findPageForMenuEntry($properties['id'], $language['code']);
                        $resolvedId = $properties['id'];
                        if ($page !== null) {
                            $pagePath = (new PageRenderer($this->builder, $page))->getPath();
                            // a localized page id may differ from its config ID (e.g. "news" rendered
                            // as "actualites"): reuse an existing entry pointing to the same page
                            $resolvedId = $this->findEntryIdByUrl($menu, $pagePath) ?? $page->getIdWithoutLang();
                            // create a base entry from the page if none exists yet
                            if (!$menu->has($resolvedId)) {
                                $menu->add((new Entry($resolvedId))
                                    ->setName($page->getVariable('title'))
                                    ->setUrl($pagePath));
                            }
                        }

                        /** @var \Cecil\Collection\Menu\Entry $item */
                        $item = (new Entry($resolvedId))
                            ->setName($properties['name'] ?? ucfirst($properties['id']))
                            ->setUrl($properties['url'] ?? '404')
                            ->setWeight((int) ($properties['weight'] ?? 0));
                        // is entry already exists?
                        if ($menu->has($resolvedId)) {
                            // removes a not enabled entry
                            if (isset($properties['enabled']) && $properties['enabled'] === false) {
                                $menu->remove($resolvedId);

                                $message = \sprintf('Config menu entry "%s (%s) > %s" removed', (string) $menu, $language['code'], $properties['id']);
                                $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                                continue;
                            }
                            // merges properties
                            $current = $menu->get($resolvedId)->toArray();
                            $properties = array_merge($current, $properties);
                            /** @var \Cecil\Collection\Menu\Entry $item */
                            $item = clone $menu->get($resolvedId);
                            $item->setName($properties['name'])
                                ->setUrl($properties['url'])
                                ->setWeight($properties['weight']);
                            $updated = true;
                        }
                        // abord if entry is not enabled
                        if (isset($properties['enabled']) && $properties['enabled'] === false) {
                            continue;
                        }
                        // adds/replaces entry
                        $menu->add($item);

                        $message = \sprintf('Config menu entry "%s (%s) > %s" %s {name: %s, url: %s, weight: %s}', (string) $menu, $language['code'], $item->getId(), $updated ? 'updated' : 'created', $item-> getName(), $item->getUrl(), $item->getWeight());
                        $this->builder->getLogger()->info($message, ['progress' => [$countConfig, $totalConfig]]);
                    }
                }
            }
        }

        $this->builder->setMenus($this->menus);
    }

    /**
     * Find the page matching a config menu entry ID, in the given language.
     *
     * Matching is tried by priority: exact ID, last path component (sub-sections),
     * then section name (section index pages with a localized path, e.g. "news" -> "actualites").
     *
     * @param string $configId  The ID from the config (may be partial)
     * @param string $language  The language code
     *
     * @return Page|null The matching page, or null if none found
     */
    protected function findPageForMenuEntry(string $configId, string $language): ?Page
    {
        $byLastPart = null;
        $bySection = null;
        foreach ($this->builder->getPages() as $page) {
            if ($page->getVariable('language') !== $language) {
                continue;
            }
            // exact ID match wins immediately
            if ($page->getId() === $configId || $page->getIdWithoutLang() === $configId) {
                return $page;
            }
            // fallback: last path component (e.g. "post" matches "blog/2024/post")
            if ($byLastPart === null && \basename($page->getIdWithoutLang()) === $configId) {
                $byLastPart = $page;
            }
            // fallback: section name of a section index (stays "news" even if path is "actualites")
            if ($bySection === null && $page->getVariable('type') === 'section' && $page->getSection() === $configId) {
                $bySection = $page;
            }
        }

        return $byLastPart ?? $bySection;
    }

    /**
     * Return the ID of the menu entry pointing to the given URL, or null if none.
     */
    private function findEntryIdByUrl(Menu $menu, ?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        foreach ($menu as $entry) {
            if ($entry->getUrl() === $url) {
                return $entry->getId();
            }
        }

        return null;
    }

    /**
     * Create menus from pages' `menu` variable.
     */
    protected function createMenusFromPages(): void
    {
        $validLanguages = array_column($this->config->getLanguages(), 'code');
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) use ($validLanguages) {
            return $page->hasVariable('menu')
                && $page->getVariable('published') === true
                && \in_array($page->getVariable('language', $this->config->getLanguageDefault()), $validLanguages);
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
             * case 1:
             *   menu: [main, navigation]
             * case 2:
             *   menu:
             *     main:
             *       weight: 999
             */
            if (\is_array($page->getVariable('menu'))) {
                foreach ($page->getVariable('menu') as $key => $value) {
                    $menuName = $key;
                    $properties = $value;
                    if (\is_int($key)) {
                        $menuName = $value;
                        $properties = null;
                    }
                    if (!\is_string($menuName)) {
                        $this->builder->getLogger()->error(\sprintf('Menu\'s name of page "%s" must be a string, not "%s"', $page->getId(), PrintLogger::format($menuName)), ['progress' => [$count, $total]]);
                        continue;
                    }
                    $item = (new Entry($page->getIdWithoutLang()))
                        ->setName($page->getVariable('title'))
                        ->setUrl((new PageRenderer($this->builder, $page))->getPath());
                    if (isset($properties['name'])) {
                        $item->setName((string) $properties['name']);
                    }
                    if (isset($properties['weight'])) {
                        $item->setWeight((int) $properties['weight']);
                    }
                    // add Menu if not exists
                    if (!$this->menus[$language]->has($menuName)) {
                        $this->menus[$language]->add(new Menu($menuName));
                    }
                    /** @var \Cecil\Collection\Menu\Menu $menu */
                    $menu = $this->menus[$language]->get($menuName);
                    // skip if an entry already points to the same page (e.g. duplicate localized section)
                    if ($this->findEntryIdByUrl($menu, $item->getUrl()) !== null) {
                        continue;
                    }
                    $menu->add($item);

                    $message = \sprintf('Page menu entry "%s (%s) > %s" created {name: %s, weight: %s}', $menu->getId(), $language, $item->getId(), $item->getName(), $properties['weight'] ?? 'N/A');
                    $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
                }
                continue;
            }
            /**
             * String case.
             *
             * e.g.:
             *   menu: main
             */
            $item = (new Entry($page->getIdWithoutLang()))
                ->setName($page->getVariable('title'))
                ->setUrl((new PageRenderer($this->builder, $page))->getPath());
            // add Menu if not exists
            if (!$this->menus[$language]->has($page->getVariable('menu'))) {
                $this->menus[$language]->add(new Menu($page->getVariable('menu')));
            }
            /** @var \Cecil\Collection\Menu\Menu $menu */
            $menu = $this->menus[$language]->get($page->getVariable('menu'));
            // skip if an entry already points to the same page (e.g. duplicate localized section)
            if ($this->findEntryIdByUrl($menu, $item->getUrl()) !== null) {
                continue;
            }
            $menu->add($item);

            $message = \sprintf('Page menu entry "%s (%s) > %s" created {name: %s}', $menu->getId(), $language, $item->getId(), $item->getName());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
    }
}
