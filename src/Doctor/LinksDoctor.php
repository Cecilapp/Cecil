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

namespace Cecil\Doctor;

use Cecil\Builder;
use Cecil\Cache;
use Cecil\Collection\Page\Page;
use Cecil\Doctor\Links\CurlUrlChecker;
use Cecil\Doctor\Links\LinkExtractor;
use Cecil\Doctor\Links\TargetIndex;
use Cecil\Doctor\Links\UrlCheckerInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Page as PageRenderer;
use Symfony\Component\Filesystem\Path;

/**
 * Links audit domain service.
 *
 * Builds the site in dry-run mode, then checks the links found in the rendered
 * HTML pages and in the linked CSS files:
 * - internal links must target a generated page, a static file or an asset,
 *   and their fragment (if any) must match an element ID of the target page;
 * - external links are requested over HTTP.
 *
 * HTML/CSS parsing and generated files indexing are delegated to `Links\LinkExtractor`
 * and `Links\TargetIndex`: the remaining complexity comes from the link checking rules.
 *
 * @SuppressWarnings(ExcessiveClassComplexity)
 */
class LinksDoctor
{
    /** Default configuration */
    private const array DEFAULT_CONFIG = [
        'internal'    => true,
        'external'    => false,
        'timeout'     => 10,
        'concurrency' => 10,
        'cache_ttl'   => 86400,
        'exclude'     => [],
        'useragent'   => null,
    ];

    /** HTTP status codes often returned by bot protections */
    private const array BLOCKED_STATUS = [401, 403, 429, 999];

    private UrlCheckerInterface $checker;

    private LinkExtractor $extractor;

    private Builder $builder;

    private TargetIndex $targets;

    /** @var array{internal: bool, external: bool, timeout: int, concurrency: int, cache_ttl: int, exclude: array<int, string>, useragent: string} exclusion patterns are stored as regular expressions */
    private array $config;

    /** @var array<string, string> CSS files to check: output path => site path */
    private array $cssFiles = [];

    /** @var array<string, array<int, array{page: string, link: string}>> external URLs and where they are used */
    private array $externalLinks = [];

    /** @var array<string, array{page: string, level: string, type: string, link: string, details: string}> */
    private array $findings = [];

    private string $baseHost = '';

    private string $basePath = '/';

    private int $linksChecked = 0;

    public function __construct(?UrlCheckerInterface $checker = null)
    {
        $this->checker = $checker ?? new CurlUrlChecker();
        $this->extractor = new LinkExtractor();
    }

    /**
     * @param array{
     *   page?: string,
     *   include_virtual?: bool,
     *   feedback?: bool,
     *   internal?: bool|null,
     *   external?: bool|null,
     *   exclude?: array<int, string>,
     *   timeout?: int|null,
     *   concurrency?: int|null,
     *   cache?: bool
     * } $options
     *
     * @return array{
     *   summary: array{
     *     pages_audited: int,
     *     links_checked: int,
     *     external_urls: int,
     *     bad_count: int,
     *     ok_count: int,
     *     feedback_count: int
     *   },
     *   findings: array<int, array{page: string, level: string, type: string, link: string, details: string}>
     * }
     */
    public function audit(Builder $builder, array $options = []): array
    {
        // resets the state of a previous audit
        $this->builder = $builder;
        $this->cssFiles = [];
        $this->externalLinks = [];
        $this->findings = [];
        $this->linksChecked = 0;
        $this->config = $this->loadConfiguration($builder, $options);

        // the whole site is always built: links of the audited page(s) may target any generated file
        $builder->build([
            'dry-run' => true,
            'page' => '',
            'render-subset' => '',
            'drafts' => false,
        ]);

        $this->targets = new TargetIndex($builder, $this->extractor);
        $pages = $this->getAuditedPages(
            str_replace('\\', '/', (string) ($options['page'] ?? '')),
            (bool) ($options['include_virtual'] ?? false)
        );

        foreach ($pages as $page) {
            $this->auditPage($page);
        }
        $this->auditCssFiles();
        if ($this->config['external']) {
            $this->auditExternalLinks((bool) ($options['cache'] ?? true));
        }

        $findings = array_values($this->findings);
        if (!($options['feedback'] ?? false)) {
            $findings = array_values(array_filter($findings, static fn (array $finding): bool => $finding['level'] !== 'feedback'));
        }
        $counts = ['bad' => 0, 'ok' => 0, 'feedback' => 0];
        foreach ($findings as $finding) {
            $counts[$finding['level']]++;
        }

        return [
            'summary' => [
                'pages_audited' => \count($pages),
                'links_checked' => $this->linksChecked,
                'external_urls' => \count($this->externalLinks),
                'bad_count' => $counts['bad'],
                'ok_count' => $counts['ok'],
                'feedback_count' => $counts['feedback'],
            ],
            'findings' => $findings,
        ];
    }

    /**
     * Merges default configuration, site configuration and command options.
     *
     * @param array<string, mixed> $options
     *
     * @return array{internal: bool, external: bool, timeout: int, concurrency: int, cache_ttl: int, exclude: array<int, string>, useragent: string}
     */
    private function loadConfiguration(Builder $builder, array $options): array
    {
        $config = array_merge(self::DEFAULT_CONFIG, (array) ($builder->getConfig()->get('doctor.links') ?? []));
        foreach (['internal', 'external', 'timeout', 'concurrency'] as $key) {
            if (isset($options[$key])) {
                $config[$key] = $options[$key];
            }
        }
        // exclusion patterns are converted to case-insensitive regular expressions
        $exclude = [];
        foreach (array_merge((array) $config['exclude'], (array) ($options['exclude'] ?? [])) as $pattern) {
            $regex = '#' . str_replace('#', '\#', (string) $pattern) . '#i';
            if (@preg_match($regex, '') === false) {
                throw new RuntimeException(\sprintf('Invalid links exclusion pattern "%s".', $pattern));
            }
            $exclude[] = $regex;
        }

        $baseurl = parse_url((string) $builder->getConfig()->get('baseurl'));
        $this->baseHost = strtolower((string) ($baseurl['host'] ?? ''));
        $this->basePath = '/' . trim((string) ($baseurl['path'] ?? ''), '/') . '/';
        $this->basePath = str_replace('//', '/', $this->basePath);

        return [
            'internal' => (bool) $config['internal'],
            'external' => (bool) $config['external'],
            'timeout' => (int) $config['timeout'],
            'concurrency' => (int) $config['concurrency'],
            'cache_ttl' => (int) $config['cache_ttl'],
            'exclude' => $exclude,
            'useragent' => (string) ($config['useragent'] ?? $builder->getConfig()->get('assets.remote.useragent.default') ?? 'Cecil'),
        ];
    }

    /**
     * Returns published and rendered HTML pages to audit.
     *
     * @return array<int, Page>
     */
    private function getAuditedPages(string $filepath, bool $includeVirtual): array
    {
        $pages = $this->builder->getPages()->filter(function (Page $page) use ($filepath, $includeVirtual) {
            if ($page->getVariable('published') !== true) {
                return false;
            }
            if (!isset($page->getRendered()['html']['output'])) {
                return false;
            }
            if (!$includeVirtual && $page->isVirtual()) {
                return false;
            }
            if ($filepath !== '' && str_replace('\\', '/', (string) $page->getVariable('filepath')) !== $filepath) {
                return false;
            }

            return true;
        });

        return array_values($pages->toArray());
    }

    /**
     * Checks links of a rendered HTML page.
     */
    private function auditPage(Page $page): void
    {
        $label = '/' . ltrim((new PageRenderer($this->builder, $page))->getPath('html'), '/');
        foreach ($this->extractor->extractHtmlLinks((string) $page->getRendered()['html']['output']) as $link) {
            $this->checkLink($label, $label, $link);
        }
    }

    /**
     * Checks links of CSS files linked from the audited pages.
     */
    private function auditCssFiles(): void
    {
        $done = [];
        while (null !== $key = array_key_first(array_diff_key($this->cssFiles, $done))) {
            $done[$key] = true;
            $content = $this->targets->getContent($key);
            if ($content === null) {
                continue;
            }
            foreach ($this->extractor->extractCssLinks($content) as $link) {
                $this->checkLink($this->cssFiles[$key], $this->cssFiles[$key], $link);
            }
        }
    }

    /**
     * Classifies a link and checks it (internal) or collects it (external).
     *
     * @param string $label    Label of the page (or CSS file) containing the link
     * @param string $location Site path of the page (or CSS file), to resolve relative URLs
     */
    private function checkLink(string $label, string $location, string $link): void
    {
        $link = trim($link);
        if ($link === '' || $link === '#' || $this->isExcluded($link)) {
            return;
        }
        if (str_starts_with($link, '//')) {
            $link = 'https:' . $link;
        }
        if (preg_match('#^([a-z][a-z0-9+.-]*):#i', $link, $matches)) {
            $scheme = strtolower($matches[1]);
            if ($scheme !== 'http' && $scheme !== 'https') {
                return;
            }
            $url = parse_url($link);
            if ($url === false || empty($url['host'])) {
                $this->addFinding($label, 'bad', 'external', $link, 'Malformed URL.');

                return;
            }
            $path = '/' . ltrim((string) ($url['path'] ?? ''), '/');
            // absolute URL to the site itself
            if ($this->baseHost !== '' && strtolower($url['host']) === $this->baseHost && str_starts_with($path . '/', $this->basePath)) {
                $this->checkInternalLink($label, $link, '/' . substr($path, \strlen($this->basePath)), (string) ($url['fragment'] ?? ''));

                return;
            }
            if ($this->config['external']) {
                $this->linksChecked++;
                $this->externalLinks[explode('#', $link, 2)[0]][] = ['page' => $label, 'link' => $link];
            }

            return;
        }

        // relative URL
        [$path, $fragment] = explode('#', $link, 2) + [1 => ''];
        $path = explode('?', $path, 2)[0];
        $this->checkInternalLink($label, $link, $this->getResolvedPath($path, $location), $fragment);
    }

    /**
     * Resolves a relative URL path against the site path of the page (or CSS file) containing it.
     */
    private function getResolvedPath(string $path, string $location): string
    {
        if ($path === '') {
            return $location;
        }
        if (!str_starts_with($path, '/')) {
            return substr($location, 0, (int) strrpos($location, '/') + 1) . $path;
        }
        if ($this->basePath !== '/' && str_starts_with($path . '/', $this->basePath)) {
            return '/' . substr($path, \strlen($this->basePath));
        }

        return $path;
    }

    /**
     * Checks that an internal link targets a generated file, and the fragment an existing ID.
     */
    private function checkInternalLink(string $label, string $link, string $path, string $fragment): void
    {
        $trailingSlash = str_ends_with($path, '/') || preg_match('#/\.{1,2}$#', $path);
        $path = Path::canonicalize(rawurldecode($path));
        $key = trim($path, '/');
        $sitePath = '/' . $key;
        if ($trailingSlash && $key !== '') {
            $key .= '/';
            $sitePath .= '/';
        }

        $missingSlash = false;
        if ($key === '' || str_ends_with($key, '/')) {
            $key .= 'index.html';
        } elseif (!$this->targets->has($key) && $this->targets->has($key . '/index.html')) {
            $key .= '/index.html';
            $missingSlash = true;
        }

        if ($this->targets->has($key) && strtolower(pathinfo($key, PATHINFO_EXTENSION)) === 'css') {
            $this->cssFiles[$key] ??= $sitePath;
        }
        if (!$this->config['internal']) {
            return;
        }

        $this->linksChecked++;
        if (!$this->targets->has($key)) {
            $this->addFinding($label, 'bad', 'internal', $link, \sprintf('Target "%s" not found.', $sitePath));

            return;
        }
        if ($missingSlash) {
            $this->addFinding($label, 'ok', 'internal', $link, \sprintf('Missing trailing slash: the server may redirect to "%s/".', $sitePath));
        }

        $fragment = rawurldecode($fragment);
        if ($fragment === '' || $fragment === 'top' || str_starts_with($fragment, ':~:')) {
            return;
        }
        $ids = $this->targets->getIds($key);
        if ($ids !== null && !isset($ids[$fragment])) {
            $this->addFinding($label, 'bad', 'anchor', $link, \sprintf('Anchor "#%s" not found in "%s".', $fragment, $sitePath));
        }
    }

    /**
     * Requests external URLs and reports failures and redirections.
     */
    private function auditExternalLinks(bool $useCache): void
    {
        $useCache = $useCache && $this->builder->getConfig()->isEnabled('cache');
        $cache = new Cache($this->builder, 'doctor-links');
        $results = [];
        $toCheck = [];
        foreach (array_keys($this->externalLinks) as $url) {
            $cached = $useCache ? $cache->get($this->getCacheKey($url)) : null;
            if (\is_array($cached) && \is_int($cached['status'] ?? null) && \is_int($cached['errno'] ?? null) && \is_string($cached['error'] ?? null) && \is_string($cached['redirect'] ?? null)) {
                $results[$url] = [
                    'status' => $cached['status'],
                    'error' => $cached['error'],
                    'errno' => $cached['errno'],
                    'redirect' => $cached['redirect'],
                ];

                continue;
            }
            $toCheck[] = $url;
        }

        if (!empty($toCheck)) {
            $checked = $this->checker->check($toCheck, [
                'timeout' => $this->config['timeout'],
                'concurrency' => $this->config['concurrency'],
                'useragent' => $this->config['useragent'],
            ]);
            foreach ($checked as $url => $result) {
                $results[$url] = $result;
                // only successful results are cached: failures are checked again on next run
                if ($useCache && $result['errno'] === 0 && $result['status'] >= 200 && $result['status'] < 400) {
                    $cache->set($this->getCacheKey($url), $result, $this->config['cache_ttl']);
                }
            }
        }

        foreach ($this->externalLinks as $url => $usages) {
            if (!isset($results[$url])) {
                continue;
            }
            $finding = $this->getExternalFinding($url, $results[$url]);
            if ($finding === null) {
                continue;
            }
            foreach ($usages as $usage) {
                $this->addFinding($usage['page'], $finding[0], 'external', $usage['link'], $finding[1]);
            }
        }
    }

    /**
     * Returns level and details of an external URL check result, or null if the URL is fine.
     *
     * @param array{status: int, error: string, errno: int, redirect: string} $result
     *
     * @return array{0: string, 1: string}|null
     */
    private function getExternalFinding(string $url, array $result): ?array
    {
        $status = $result['status'];
        if ($result['errno'] !== 0) {
            if ($result['errno'] === 28) { // CURLE_OPERATION_TIMEDOUT
                return ['feedback', \sprintf('Timeout: %s', $result['error'])];
            }

            return ['bad', $result['error'] !== '' ? $result['error'] : \sprintf('Request failed (error %d).', $result['errno'])];
        }
        if (\in_array($status, self::BLOCKED_STATUS, true)) {
            return ['feedback', \sprintf('HTTP %d: access denied, possibly by a bot protection.', $status)];
        }
        if ($status >= 400 || $status === 0) {
            return ['bad', \sprintf('HTTP %d.', $status)];
        }
        if ($result['redirect'] !== '' && $result['redirect'] !== $url) {
            if (str_starts_with($url, 'http://') && 'https://' . substr($url, 7) === $result['redirect']) {
                return ['ok', \sprintf('Redirects to HTTPS: "%s".', $result['redirect'])];
            }

            return ['ok', \sprintf('Redirects to "%s".', $result['redirect'])];
        }

        return null;
    }

    private function getCacheKey(string $url): string
    {
        return 'url_' . hash('sha256', $url);
    }

    /**
     * Is the link matching an exclusion pattern?
     */
    private function isExcluded(string $link): bool
    {
        foreach ($this->config['exclude'] as $regex) {
            if (preg_match($regex, $link)) {
                return true;
            }
        }

        return false;
    }

    private function addFinding(string $page, string $level, string $type, string $link, string $details): void
    {
        $this->findings[implode("\0", [$page, $level, $type, $link, $details])] = [
            'page' => $page,
            'level' => $level,
            'type' => $type,
            'link' => $link,
            'details' => $details,
        ];
    }
}
