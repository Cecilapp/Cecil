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

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\PrefixSuffix;
use Cecil\Collection\Page\Type;
use Cecil\Exception\RuntimeException;

/**
 * Section generator class.
 *
 * This class is responsible for generating sections from the pages in the builder.
 * It identifies sections based on the 'section' variable in each page, and
 * creates a new page for each section. The generated pages are added to the
 * collection of generated pages. It also handles sorting of subpages and
 * adding navigation links (next and previous) to the section pages.
 *
 * Sub-sections support:
 * When a subfolder inside pages/ contains an index.md file, it is treated as a
 * sub-section. Pages within that subfolder are assigned to the sub-section rather
 * than the root section. Parent/child relationships are established between
 * sections to form a tree structure.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        // Step 1: Detect nested section paths (subfolders with index.md).
        // Returns a map of slugified-folder-path => page-id.
        $nestedSectionPaths = $this->detectNestedSectionPaths();

        // Build a reverse map: page-id => folder-path (for looking up a page's original folder).
        $pageIdToFolderPath = [];
        foreach ($this->builder->getPages() ?? [] as $p) {
            $filepath = $p->getVariable('filepath');
            if ($filepath) {
                $dir = str_replace(DIRECTORY_SEPARATOR, '/', \dirname($filepath));
                $folderPath = ($dir === '.') ? '' : Page::slugify($dir);
                $pageIdToFolderPath[$p->getId()] = $folderPath;
            }
        }

        // Step 2: Group pages into sections (deepest matching section).
        $sections = [];

        /** @var Page $page */
        foreach ($this->builder->getPages() ?? [] as $page) {
            if (!$page->getSection()) {
                continue;
            }
            // do not add "not published" and "not excluded" pages to its section
            if (
                $page->getVariable('published') !== true
                || ($page->getVariable('excluded') || $page->getVariable('exclude'))
            ) {
                continue;
            }

            $language = $page->getVariable('language', $this->config->getLanguageDefault());
            $pageId = $page->getId();

            // Use the original file folder path to resolve the section.
            $originalFolder = $pageIdToFolderPath[$pageId] ?? null;
            $sectionPath = $this->resolveSection($originalFolder, $page->getSection(), $nestedSectionPaths);

            // Don't add a section's own index page to its pages list.
            // A page is a section index if its page ID matches a nested section path.
            if ($pageId === $sectionPath || isset($nestedSectionPaths[$pageId])) {
                continue;
            }

            // Root section index pages: their path equals their section.
            $pagePath = $page->getPath();
            if ($pagePath === $page->getSection()) {
                continue;
            }

            // Update the page's section to the resolved (possibly nested) section path.
            if ($sectionPath !== $page->getSection()) {
                $page->setSection($sectionPath);
            }

            $sections[$sectionPath][$language][] = $page;
        }

        // Ensure all nested section paths exist in the sections map (even if empty).
        // Also ensure their parent sections exist.
        foreach ($nestedSectionPaths as $nestedPath => $_) {
            if (!isset($sections[$nestedPath])) {
                // Determine language from the index page
                if ($this->builder->getPages()->has($nestedPath)) {
                    $indexPage = $this->builder->getPages()->get($nestedPath);
                    $lang = $indexPage->getVariable('language', $this->config->getLanguageDefault());
                    $sections[$nestedPath][$lang] = [];
                }
            }

            // Walk up parent paths and ensure they exist as sections too.
            $parts = explode('/', $nestedPath);
            array_pop($parts);
            while (!empty($parts)) {
                $parentPath = implode('/', $parts);
                if (!isset($sections[$parentPath])) {
                    $parentSlug = Page::slugify($parentPath);
                    if ($this->builder->getPages()->has($parentSlug)) {
                        $indexPage = $this->builder->getPages()->get($parentSlug);
                        $lang = $indexPage->getVariable('language', $this->config->getLanguageDefault());
                        $sections[$parentPath][$lang] = [];
                    }
                }
                array_pop($parts);
            }
        }

        // Step 3: Create section pages.
        if (\count($sections) > 0) {
            $menuWeight = 100;

            // Sort section keys so parents are processed before children.
            $sectionKeys = array_keys($sections);
            usort($sectionKeys, function (string $a, string $b): int {
                return substr_count($a, '/') <=> substr_count($b, '/');
            });

            $sectionPages = []; // maps sectionPath/language => Page

            foreach ($sectionKeys as $section) {
                $languages = $sections[$section];
                foreach ($languages as $language => $pagesAsArray) {
                    $pageId = $path = Page::slugify($section);
                    if ($language != $this->config->getLanguageDefault()) {
                        $pageId = "$language/$pageId";
                    }
                    $page = (new Page($pageId))->setVariable('title', ucfirst(basename($section)))
                        ->setPath($path);
                    if ($this->builder->getPages()->has($pageId)) {
                        $page = clone $this->builder->getPages()->get($pageId);
                    }
                    $pages = new PagesCollection("section-$pageId", $pagesAsArray);
                    // cascade variables
                    if ($page->hasVariable('cascade')) {
                        $cascade = $page->getVariable('cascade');
                        if (\is_array($cascade)) {
                            $pages->map(function (Page $page) use ($cascade) {
                                foreach ($cascade as $key => $value) {
                                    if (!$page->hasVariable($key)) {
                                        $page->setVariable($key, $value);
                                    }
                                }
                            });
                        }
                    }
                    // sorts pages
                    $sortBy = $page->getVariable('sortby') ?? $this->config->get('pages.sortby');
                    $pages = $pages->sortBy($sortBy);
                    // adds navigation links (excludes taxonomy pages)
                    $sortByVar = $page->getVariable('sortby')['variable'] ?? $page->getVariable('sortby') ?? $this->config->get('pages.sortby')['variable'] ?? $this->config->get('pages.sortby') ?? 'date';
                    if (!\in_array($page->getId(), array_keys((array) $this->config->get('taxonomies')))) {
                        $this->addNavigationLinks($pages, $sortByVar, $page->getVariable('circular') ?? false);
                    }
                    // creates page for each section
                    $page->setType(Type::SECTION->value)
                        ->setSection($path)
                        ->setPages($pages)
                        ->setVariable('language', $language)
                        ->setVariable('langref', $path);
                    if ($pages->first()) {
                        $page->setVariable('date', $pages->first()->getVariable('date'));
                    }
                    // human readable title
                    if ($page->getVariable('title') == 'index') {
                        $page->setVariable('title', basename($section));
                    }
                    // default menu: only root sections get a default menu entry
                    if (!str_contains($section, '/') && !$page->getVariable('menu')) {
                        $page->setVariable('menu', ['main' => ['weight' => $menuWeight]]);
                    }

                    try {
                        $this->generatedPages->add($page);
                    } catch (\DomainException) {
                        $this->generatedPages->replace($page->getId(), $page);
                    }

                    $sectionPages["$path|$language"] = $page;
                }

                if (!str_contains($section, '/')) {
                    $menuWeight += 10;
                }
            }

            // Step 4: Build parent/child relationships between sections.
            $this->buildSectionTree($sectionPages, $nestedSectionPaths);
        }
    }

    /**
     * Detects nested section paths by finding pages created from index.md files
     * that are in subdirectories (nested deeper than the root section level).
     *
     * Uses original file paths (not transformed page paths) to correctly detect
     * hierarchy even when custom path patterns (e.g., date-based paths) are configured.
     *
     * @return array<string, true> Map of nested section paths (slugified folder paths)
     */
    protected function detectNestedSectionPaths(): array
    {
        $nestedPaths = [];

        /** @var Page $page */
        foreach ($this->builder->getPages() ?? [] as $page) {
            if ($page->isVirtual() || $page->getType() === Type::HOMEPAGE->value) {
                continue;
            }

            $filepath = $page->getVariable('filepath');
            if (!$filepath) {
                continue;
            }

            // Get the original directory from the filepath.
            $dir = str_replace(DIRECTORY_SEPARATOR, '/', \dirname($filepath));
            if ($dir === '.' || !str_contains($dir, '/')) {
                continue; // Root-level folders are not "nested"
            }

            // Check if this page was created from an index file.
            $extension = pathinfo($filepath, PATHINFO_EXTENSION);
            $filename = basename($filepath, '.' . $extension);
            $cleanName = strtolower(PrefixSuffix::sub($filename));

            if ($cleanName === 'index' || $cleanName === 'readme') {
                // Use the slugified directory as the nested section path.
                $folderPath = Page::slugify($dir);
                $nestedPaths[$folderPath] = true;
            }
        }

        return $nestedPaths;
    }

    /**
     * Resolves the deepest matching section for a page based on its original folder path.
     *
     * If the page's original folder matches a nested section path, it is assigned to
     * that sub-section. Otherwise, it stays in its root section.
     *
     * @param string|null          $originalFolder     The page's original file folder (slugified)
     * @param string               $rootSection        The page's current root section
     * @param array<string, true>  $nestedSectionPaths Map of nested section paths
     *
     * @return string The resolved section path
     */
    protected function resolveSection(?string $originalFolder, string $rootSection, array $nestedSectionPaths): string
    {
        if ($originalFolder === null || empty($nestedSectionPaths)) {
            return $rootSection;
        }

        // Try to find the deepest nested section matching this page's original folder.
        // Start from the full folder path and walk up.
        $parts = explode('/', $originalFolder);

        while (!empty($parts)) {
            $candidate = implode('/', $parts);
            if (isset($nestedSectionPaths[$candidate])) {
                return $candidate;
            }
            array_pop($parts);
        }

        return $rootSection;
    }

    /**
     * Builds parent/child relationships between section pages.
     *
     * @param array<string, Page>   $sectionPages       Map of "path|language" => section Page
     * @param array<string, true>   $nestedSectionPaths Map of nested section paths
     */
    protected function buildSectionTree(array $sectionPages, array $nestedSectionPaths): void
    {
        foreach ($sectionPages as $key => $sectionPage) {
            [$path, $language] = explode('|', $key);

            if (!str_contains($path, '/')) {
                continue; // Root sections have no parent
            }

            // Find the closest parent section.
            $parts = explode('/', $path);
            array_pop($parts);

            while (!empty($parts)) {
                $parentPath = implode('/', $parts);
                $parentKey = "$parentPath|$language";

                if (isset($sectionPages[$parentKey])) {
                    $parentPage = $sectionPages[$parentKey];

                    // Set parent/child relationship
                    $sectionPage->setParentSection($parentPage);
                    $parentPage->addSubSection($sectionPage);

                    // Update generated pages collections
                    try {
                        $this->generatedPages->replace($sectionPage->getId(), $sectionPage);
                    } catch (\DomainException) {
                        // ignore
                    }
                    try {
                        $this->generatedPages->replace($parentPage->getId(), $parentPage);
                    } catch (\DomainException) {
                        // ignore
                    }

                    break;
                }

                array_pop($parts);
            }
        }
    }

    /**
     * Adds navigation (next and prev) to each pages of a section.
     */
    protected function addNavigationLinks(PagesCollection $pages, string|null $sortBy = null, bool $circular = false): void
    {
        $pagesAsArray = $pages->toArray();
        if ($sortBy === null || $sortBy == 'date' || $sortBy == 'updated') {
            $pagesAsArray = array_reverse($pagesAsArray);
        }
        $count = \count($pagesAsArray);
        if ($count > 1) {
            foreach ($pagesAsArray as $position => $page) {
                switch ($position) {
                    case 0: // first
                        if ($circular) {
                            $page->setVariables([
                                'prev' => $pagesAsArray[$count - 1],
                            ]);
                        }
                        $page->setVariables([
                            'next' => $pagesAsArray[$position + 1],
                        ]);
                        break;
                    case $count - 1: // last
                        $page->setVariables([
                            'prev' => $pagesAsArray[$position - 1],
                        ]);
                        if ($circular) {
                            $page->setVariables([
                                'next' => $pagesAsArray[0],
                            ]);
                        }
                        break;
                    default:
                        $page->setVariables([
                            'prev' => $pagesAsArray[$position - 1],
                            'next' => $pagesAsArray[$position + 1],
                        ]);
                        break;
                }
                $this->generatedPages->add($page);
            }
        }
    }
}
