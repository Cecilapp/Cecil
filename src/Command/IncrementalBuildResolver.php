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

namespace Cecil\Command;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Renderer\Layout;
use Cecil\Util;
use Symfony\Component\Finder\Finder;
use Yosymfony\ResourceWatcher\ResourceWatcherResult;

/**
 * Resolves impacted source pages for incremental serve builds.
 */
class IncrementalBuildResolver
{
    public function __construct(
        private Builder $builder,
        private bool $includeDrafts = false,
    ) {
    }

    /**
     * Resolves the list of content pages to rebuild from watcher changes.
     *
     * Returns an array of page paths (relative to the pages directory) when an incremental
     * rebuild is possible, or `null` when a full rebuild is required.
     *
     * @return array<int, string>|null
     */
    public function resolve(ResourceWatcherResult $watcher): ?array
    {
        // A deletion may impact lists, sections, taxonomies, etc.: full rebuild.
        if (\count($watcher->getDeletedFiles()) > 0) {
            return null;
        }

        $changedFiles = array_merge($watcher->getNewFiles(), $watcher->getUpdatedFiles());
        if (\count($changedFiles) === 0) {
            return null;
        }

        $config = $this->builder->getConfig();
        $pagesPath = $this->normalizePath($config->getPagesPath());
        $extensions = array_map('strtolower', (array) $config->get('pages.ext'));

        $pages = [];
        $templates = [];
        foreach ($changedFiles as $file) {
            $normalized = $this->normalizePath((string) $file);
            if ($templateRef = $this->resolveTemplateRefFromPath($normalized)) {
                $templates[] = $templateRef;

                continue;
            }
            // File extension must be a valid page extension.
            $extension = strtolower((string) pathinfo($normalized, PATHINFO_EXTENSION));
            if (!\in_array($extension, $extensions, true)) {
                return null;
            }
            // The changed file must belong to the pages directory (otherwise treat as "other" change).
            if (strncmp($normalized, $pagesPath . '/', \strlen($pagesPath) + 1) !== 0) {
                return null;
            }
            $relative = $this->relativePathFromDirectory($normalized, $pagesPath);
            // If the file is excluded from pages discovery, it is not safe to do a partial build.
            if (\is_array($exclude = $config->get('pages.exclude'))) {
                foreach ($exclude as $pattern) {
                    $pattern = (string) $pattern;
                    if ($pattern !== '' && preg_match('#(^|/)' . preg_quote($pattern, '#') . '(/|$)#', $relative)) {
                        return null;
                    }
                }
            }
            $pages[$relative] = $relative;
        }

        if (\count($templates) > 0) {
            $pagesFromTemplates = $this->resolvePagesImpactedByTemplates($templates);
            if ($pagesFromTemplates === null) {
                return null;
            }
            foreach ($pagesFromTemplates as $page) {
                $pages[$page] = $page;
            }
        }

        return array_values($pages);
    }

    /**
     * Resolves source pages impacted by changed templates.
     *
     * @param array<int, array{scope: string, file: string}> $changedTemplates
     * @return array<int, string>|null
     */
    private function resolvePagesImpactedByTemplates(array $changedTemplates): ?array
    {
        $config = $this->builder->getConfig();
        $affectedTemplates = $this->resolveAffectedTemplates($changedTemplates);

        if (\count($affectedTemplates) === 0) {
            return [];
        }

        $pagesPath = $config->getPagesPath();
        if (!is_dir($pagesPath)) {
            return [];
        }

        $namePattern = '/\.(' . implode('|', (array) $config->get('pages.ext')) . ')$/';
        $finder = Finder::create()
            ->files()
            ->in($pagesPath)
            ->name($namePattern);

        if (\is_array($exclude = $config->get('pages.exclude'))) {
            $finder->exclude($exclude);
            $finder->notPath($exclude);
            $finder->notName($exclude);
        }
        if (file_exists(Util::joinFile($pagesPath, '.gitignore'))) {
            $finder->ignoreVCSIgnored(true);
        }

        $converter = new Converter($this->builder);
        $frontmatterFormat = (string) $config->get('pages.frontmatter');
        $impactedPages = [];

        foreach ($finder as $file) {
            $page = new Page($file);
            $page->parse();

            if ($page->getFrontmatter()) {
                try {
                    $variables = $converter->convertFrontmatter((string) $page->getFrontmatter(), $frontmatterFormat);
                    $page->setFmVariables($variables);
                    $page->setVariables($variables);
                } catch (\Throwable) {
                    // Ignore parse failures here: build subprocess will report them if the page is rebuilt.
                }
            }

            if (!$this->includeDrafts && !$page->getVariable('published')) {
                continue;
            }

            foreach ($this->getPageOutputFormats($page) as $format) {
                try {
                    $layout = Layout::finder($page, $format, $config);
                } catch (\Throwable) {
                    continue;
                }
                $layoutRef = $layout['scope'] . ':' . $layout['file'];
                if (isset($affectedTemplates[$layoutRef])) {
                    $pageFilePath = (string) $page->getVariable('filepath');
                    $impactedPages[$pageFilePath] = $pageFilePath;
                    break;
                }
            }
        }

        return array_values($impactedPages);
    }

    /**
     * Returns all templates impacted by changed templates (including reverse dependencies).
     *
     * @param array<int, array{scope: string, file: string}> $changedTemplates
     * @return array<string, bool>
     */
    private function resolveAffectedTemplates(array $changedTemplates): array
    {
        $reverseDependencies = $this->buildReverseTemplateDependencyGraph();
        $affected = [];
        $queue = [];

        foreach ($changedTemplates as $templateRef) {
            $id = $templateRef['scope'] . ':' . $templateRef['file'];
            $affected[$id] = true;
            $queue[] = $id;
        }

        while (($current = array_shift($queue)) !== null) {
            foreach ($reverseDependencies[$current] ?? [] as $dependent) {
                if (!isset($affected[$dependent])) {
                    $affected[$dependent] = true;
                    $queue[] = $dependent;
                }
            }
        }

        return $affected;
    }

    /**
     * Builds a reverse dependency graph keyed by template reference (`scope:file`).
     *
     * @return array<string, array<int, string>>
     */
    private function buildReverseTemplateDependencyGraph(): array
    {
        $reverseDependencies = [];

        foreach ($this->getTemplateRoots() as $scope => $rootPath) {
            if (!is_dir($rootPath)) {
                continue;
            }

            $finder = Finder::create()
                ->files()
                ->in($rootPath)
                ->name('/\\.' . Layout::EXT . '$/');

            foreach ($finder as $file) {
                $fileRef = $scope . ':' . str_replace('\\', '/', $file->getRelativePathname());
                $content = $file->getContents();
                if (preg_match_all('/\\{%\\s*(?:extends|include|embed|use|import|from)\\s+["\']([^"\']+)["\']/i', $content, $matches)) {
                    foreach ($matches[1] as $dependency) {
                        if (!\is_string($dependency) || $dependency === '') {
                            continue;
                        }
                        if (!str_ends_with($dependency, '.' . Layout::EXT)) {
                            continue;
                        }
                        $dependencyRef = $scope . ':' . str_replace('\\', '/', trim($dependency));
                        $reverseDependencies[$dependencyRef][$fileRef] = $fileRef;
                    }
                }
            }
        }

        foreach ($reverseDependencies as $template => $dependents) {
            $reverseDependencies[$template] = array_values($dependents);
        }

        return $reverseDependencies;
    }

    /**
     * Resolves a filesystem path to a template reference when it belongs to a template root.
     *
     * @return array{scope: string, file: string}|null
     */
    private function resolveTemplateRefFromPath(string $path): ?array
    {
        if (!str_ends_with($path, '.' . Layout::EXT)) {
            return null;
        }

        foreach ($this->getTemplateRoots() as $scope => $rootPath) {
            $normalizedRoot = $this->normalizePath($rootPath);
            if ($path === $normalizedRoot || strncmp($path, $normalizedRoot . '/', \strlen($normalizedRoot) + 1) !== 0) {
                continue;
            }

            return [
                'scope' => $scope,
                'file' => substr($path, \strlen($normalizedRoot) + 1),
            ];
        }

        return null;
    }

    /**
     * Returns template roots keyed by their scope name used by Layout::finder.
     *
     * @return array<string, string>
     */
    private function getTemplateRoots(): array
    {
        $config = $this->builder->getConfig();
        $roots = [
            'site' => $config->getLayoutsPath(),
        ];

        if ($config->hasTheme()) {
            foreach ($config->getTheme() ?? [] as $theme) {
                $roots[$theme] = $config->getThemeDirPath($theme, 'layouts');
            }
        }

        $roots['cecil'] = $config->getLayoutsInternalPath();

        return $roots;
    }

    /**
     * Returns output formats for a page using render step rules.
     *
     * @return array<int, string>
     */
    private function getPageOutputFormats(Page $page): array
    {
        $config = $this->builder->getConfig();

        if ($page->getVariable('output')) {
            $formats = $page->getVariable('output');
            if (!\is_array($formats)) {
                $formats = [$formats];
            }

            return array_values(array_unique(array_map('strval', $formats)));
        }

        $formats = $config->get('output.pagetypeformats.' . $page->getType());
        if (empty($formats)) {
            return [];
        }
        if (!\is_array($formats)) {
            $formats = [$formats];
        }

        return array_values(array_unique(array_map('strval', $formats)));
    }

    /**
     * Normalizes a filesystem path to use forward slashes without a trailing slash.
     */
    private function normalizePath(string $path): string
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');

        // Prefer canonical absolute path when possible to avoid false negatives
        // with relative segments, symlinks/junctions or drive letter variations.
        $realPath = realpath($normalized);
        if ($realPath !== false) {
            $normalized = rtrim(str_replace('\\', '/', $realPath), '/');
        }

        // Windows filesystems are case-insensitive by default.
        if (DIRECTORY_SEPARATOR === '\\') {
            $normalized = strtolower($normalized);
        }

        return $normalized;
    }

    /**
     * Returns file path relative to a directory path.
     */
    private function relativePathFromDirectory(string $filePath, string $directoryPath): string
    {
        $filePath = $this->normalizePath($filePath);
        $directoryPath = $this->normalizePath($directoryPath);

        return ltrim((string) substr($filePath, \strlen($directoryPath)), '/');
    }
}
