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
use Cecil\Doctor\Links\UrlCheckerInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Util;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Links audit domain service.
 *
 * Builds the site in dry-run mode, then checks the links found in the rendered
 * HTML pages and in the linked CSS files:
 * - internal links must target a generated page, a static file or an asset,
 *   and their fragment (if any) must match an element ID of the target page;
 * - external links are requested over HTTP.
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

    /** HTML elements and attributes containing a single URL */
    private const array URL_ATTRIBUTES = [
        'a'      => ['href'],
        'area'   => ['href'],
        'link'   => ['href'],
        'img'    => ['src'],
        'script' => ['src'],
        'iframe' => ['src'],
        'embed'  => ['src'],
        'source' => ['src'],
        'track'  => ['src'],
        'audio'  => ['src'],
        'video'  => ['src', 'poster'],
        'object' => ['data'],
    ];

    /** HTML elements with a `srcset` attribute */
    private const array SRCSET_ELEMENTS = ['img', 'source'];

    /** `<link rel>` values whose URL is not a resource to check */
    private const array SKIPPED_LINK_RELS = ['preconnect', 'dns-prefetch', 'webmention', 'pingback'];

    /** HTTP status codes often returned by bot protections */
    private const array BLOCKED_STATUS = [401, 403, 429, 999];

    private UrlCheckerInterface $checker;

    private Builder $builder;

    /** @var array{internal: bool, external: bool, timeout: int, concurrency: int, cache_ttl: int, exclude: array<int, string>, useragent: string} */
    private array $config;

    /** @var array<string, array{type: string, source: string, page?: Page, format?: string}> generated files indexed by output path */
    private array $targets = [];

    /** @var array<string, array<string, true>> element IDs indexed by output path */
    private array $ids = [];

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
        $this->reset($builder);
        $this->config = $this->loadConfiguration($builder, $options);

        // the whole site is always built: links of the audited page(s) may target any generated file
        $builder->build([
            'dry-run' => true,
            'page' => '',
            'render-subset' => '',
            'drafts' => false,
        ]);

        $this->indexTargets();
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
     * Resets the state of a previous audit.
     */
    private function reset(Builder $builder): void
    {
        $this->builder = $builder;
        $this->targets = [];
        $this->ids = [];
        $this->cssFiles = [];
        $this->externalLinks = [];
        $this->findings = [];
        $this->linksChecked = 0;
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
        $exclude = array_merge((array) $config['exclude'], (array) ($options['exclude'] ?? []));
        foreach ($exclude as $pattern) {
            if (@preg_match($this->toRegex((string) $pattern), '') === false) {
                throw new RuntimeException(\sprintf('Invalid links exclusion pattern "%s".', $pattern));
            }
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
            'exclude' => array_values(array_map('strval', $exclude)),
            'useragent' => (string) ($config['useragent'] ?? $builder->getConfig()->get('assets.remote.useragent.default') ?? 'Cecil'),
        ];
    }

    /**
     * Indexes generated pages, static files and assets by output path.
     */
    private function indexTargets(): void
    {
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            foreach (array_keys($page->getRendered()) as $format) {
                $path = (new PageRenderer($this->builder, $page))->getOutputFilePath((string) $format);
                $this->targets[ltrim($path, '/')] = ['type' => 'page', 'source' => '', 'page' => $page, 'format' => (string) $format];
            }
        }

        $config = $this->builder->getConfig();
        $target = (string) $config->get('static.target');
        $exclude = (array) $config->get('static.exclude');
        $sources = [];
        if ($config->hasTheme()) {
            foreach (array_reverse($config->getTheme() ?? []) as $theme) {
                $sources[] = [$config->getThemeDirPath($theme, 'static'), $target, $exclude];
            }
        }
        $sources[] = [$config->getStaticPath(), $target, $exclude];
        foreach ((array) $config->get('static.mounts') as $source => $destination) {
            $sources[] = [Util::joinFile($config->getStaticPath(), (string) $source), (string) $destination, []];
        }
        foreach ($sources as [$from, $to, $excluded]) {
            if (is_file($from)) {
                $this->targets[trim(str_replace('\\', '/', $to), '/')] = ['type' => 'static', 'source' => $from];

                continue;
            }
            if (!is_dir($from)) {
                continue;
            }
            $finder = Finder::create()->files()->in($from)->ignoreDotFiles(false);
            if (!empty($excluded)) {
                $finder->notPath($excluded)->notName($excluded);
            }
            foreach ($finder as $file) {
                $this->targets[ltrim(Util::joinPath($to, $file->getRelativePathname()), '/')] = ['type' => 'static', 'source' => $file->getPathname()];
            }
        }

        $cache = new Cache($this->builder, 'assets');
        foreach ($this->builder->getAssetsList() as $path) {
            $key = ltrim($path, '/');
            // a file from `static/` is not overridden by an asset
            if (!isset($this->targets[$key])) {
                $this->targets[$key] = ['type' => 'asset', 'source' => $cache->getContentFile($path)];
            }
        }
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
        $xpath = $this->createXPath((string) $page->getRendered()['html']['output']);
        if ($xpath === null) {
            return;
        }

        foreach ($this->extractHtmlLinks($xpath) as $link) {
            $this->checkLink($label, $label, $link);
        }
    }

    /**
     * Extracts URLs from HTML attributes, inline styles and `<style>` elements.
     *
     * @return array<int, string>
     */
    private function extractHtmlLinks(\DOMXPath $xpath): array
    {
        $links = [];
        foreach (self::URL_ATTRIBUTES as $element => $attributes) {
            foreach ($attributes as $attribute) {
                /** @var \DOMElement $node */
                foreach ($xpath->query(\sprintf('//%s[@%s]', $element, $attribute)) ?: [] as $node) {
                    if ($element === 'link' && array_intersect(preg_split('/\s+/', strtolower($node->getAttribute('rel'))) ?: [], self::SKIPPED_LINK_RELS)) {
                        continue;
                    }
                    $links[] = $node->getAttribute($attribute);
                }
            }
        }
        foreach (self::SRCSET_ELEMENTS as $element) {
            /** @var \DOMElement $node */
            foreach ($xpath->query(\sprintf('//%s[@srcset]', $element)) ?: [] as $node) {
                array_push($links, ...$this->parseSrcset($node->getAttribute('srcset')));
            }
        }
        foreach ($xpath->query('//style | //*[@style]/@style') ?: [] as $node) {
            if ($node instanceof \DOMNode) {
                array_push($links, ...$this->extractCssLinks($node->textContent));
            }
        }

        return $links;
    }

    /**
     * Returns URLs of a `srcset` attribute.
     *
     * @return array<int, string>
     */
    private function parseSrcset(string $srcset): array
    {
        if (str_starts_with(trim($srcset), 'data:')) {
            return [];
        }
        $urls = [];
        foreach (explode(',', $srcset) as $candidate) {
            $url = preg_split('/\s+/', trim($candidate))[0] ?? '';
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Extracts URLs from `url()` and `@import` CSS statements.
     *
     * @return array<int, string>
     */
    private function extractCssLinks(string $css): array
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        preg_match_all('/url\(\s*([\'"]?)(.*?)\1\s*\)/i', $css, $urls);
        preg_match_all('/@import\s+([\'"])(.*?)\1/i', $css, $imports);

        return array_merge($urls[2], $imports[2]);
    }

    /**
     * Checks links of CSS files linked from the audited pages.
     */
    private function auditCssFiles(): void
    {
        $done = [];
        while (null !== $key = array_key_first(array_diff_key($this->cssFiles, $done))) {
            $done[$key] = true;
            $content = $this->getTargetContent($key);
            if ($content === null) {
                continue;
            }
            foreach ($this->extractCssLinks($content) as $link) {
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
        $fragment = '';
        $path = $link;
        if (false !== $position = strpos($path, '#')) {
            $fragment = substr($path, $position + 1);
            $path = substr($path, 0, $position);
        }
        if (false !== $position = strpos($path, '?')) {
            $path = substr($path, 0, $position);
        }
        if ($path === '') {
            $path = $location;
        } elseif (str_starts_with($path, '/')) {
            if ($this->basePath !== '/' && str_starts_with($path . '/', $this->basePath)) {
                $path = '/' . substr($path, \strlen($this->basePath));
            }
        } else {
            $path = substr($location, 0, (int) strrpos($location, '/') + 1) . $path;
        }
        $this->checkInternalLink($label, $link, $path, $fragment);
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
        } elseif (!isset($this->targets[$key]) && isset($this->targets[$key . '/index.html'])) {
            $key .= '/index.html';
            $missingSlash = true;
        }

        if (isset($this->targets[$key]) && strtolower(pathinfo($key, PATHINFO_EXTENSION)) === 'css') {
            $this->cssFiles[$key] ??= $sitePath;
        }
        if (!$this->config['internal']) {
            return;
        }

        $this->linksChecked++;
        if (!isset($this->targets[$key])) {
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
        $ids = $this->getTargetIds($key);
        if ($ids !== null && !isset($ids[$fragment])) {
            $this->addFinding($label, 'bad', 'anchor', $link, \sprintf('Anchor "#%s" not found in "%s".', $fragment, $sitePath));
        }
    }

    /**
     * Returns element IDs of a generated HTML page, or null if the target is not an HTML page.
     *
     * @return array<string, true>|null
     */
    private function getTargetIds(string $key): ?array
    {
        if (!\in_array(strtolower(pathinfo($key, PATHINFO_EXTENSION)), ['html', 'htm'], true)) {
            return null;
        }
        if (!isset($this->ids[$key])) {
            $this->ids[$key] = [];
            $xpath = $this->createXPath((string) $this->getTargetContent($key));
            foreach ($xpath?->query('//*[@id]/@id | //a[@name]/@name') ?: [] as $node) {
                if ($node instanceof \DOMNode) {
                    $this->ids[$key][$node->textContent] = true;
                }
            }
        }

        return $this->ids[$key];
    }

    /**
     * Returns the content of a generated file, or null if not available.
     */
    private function getTargetContent(string $key): ?string
    {
        $target = $this->targets[$key];
        if ($target['type'] === 'page') {
            return isset($target['page'], $target['format']) ? (string) ($target['page']->getRendered()[$target['format']]['output'] ?? '') : null;
        }
        if (!is_file($target['source'])) {
            return null;
        }
        $content = Util\File::fileGetContents($target['source']);

        return $content === false ? null : $content;
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
        foreach ($this->config['exclude'] as $pattern) {
            if (preg_match($this->toRegex($pattern), $link)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts an exclusion pattern to a case-insensitive regular expression.
     */
    private function toRegex(string $pattern): string
    {
        return '#' . str_replace('#', '\#', $pattern) . '#i';
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

    private function createXPath(string $html): ?\DOMXPath
    {
        if (trim($html) === '') {
            return null;
        }

        $useInternalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        if ($loaded === false) {
            return null;
        }

        return new \DOMXPath($document);
    }
}
