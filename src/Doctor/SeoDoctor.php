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
use Cecil\Collection\Page\Page;
use Cecil\Renderer\Page as PageRenderer;

/**
 * SEO audit domain service.
 */
class SeoDoctor
{
    /** Default configuration thresholds */
    private const DEFAULT_CONFIG = [
        'title' => ['min' => 30, 'max' => 60],
        'description' => ['min' => 120, 'max' => 160],
        'content' => ['min_words' => 300],
        'checks' => [
            'title' => true,
            'description' => true,
            'canonical' => true,
            'h1' => true,
            'og_tags' => true,
            'img_alt' => true,
            'content_length' => true,
            'lang_attribute' => true,
        ],
    ];

    /**
     * @param array{page?: string, include_virtual?: bool} $options
     *
     * @return array{
     *   summary: array{
     *     pages_audited: int,
     *     pages_without_findings: int,
     *     bad_count: int,
     *     ok_count: int,
     *     feedback_count: int
     *   },
     *   findings: array<int, array{page: string, level: string, check: string, details: string}>
     * }
     */
    public function audit(Builder $builder, array $options = []): array
    {
        $includeVirtual = (bool) ($options['include_virtual'] ?? false);
        $page = (string) ($options['page'] ?? '');

        [$thresholds, $checks] = $this->loadConfiguration($builder);

        $builder->build([
            'dry-run' => true,
            'page' => $page,
            'render-subset' => '',
            'drafts' => false,
        ]);

        $pages = $builder->getPages()->filter(function (Page $currentPage) use ($includeVirtual) {
            if ($currentPage->getVariable('published') !== true) {
                return false;
            }
            if (!isset($currentPage->getRendered()['html']['output'])) {
                return false;
            }
            if (!$includeVirtual && $currentPage->isVirtual()) {
                return false;
            }

            return true;
        });

        $findings = [];
        $counts = [
            'bad' => 0,
            'ok' => 0,
            'feedback' => 0,
        ];
        $healthyPages = 0;

        /** @var Page $currentPage */
        foreach ($pages as $currentPage) {
            $pageFindings = $this->auditPage($builder, $currentPage, $thresholds, $checks);
            if (empty($pageFindings)) {
                $healthyPages++;

                continue;
            }

            foreach ($pageFindings as $finding) {
                $counts[$finding['level']]++;
                $findings[] = [
                    'page' => $this->getPageLabel($builder, $currentPage),
                    'level' => $finding['level'],
                    'check' => $finding['check'],
                    'details' => $finding['details'],
                ];
            }
        }

        return [
            'summary' => [
                'pages_audited' => \count($pages),
                'pages_without_findings' => $healthyPages,
                'bad_count' => $counts['bad'],
                'ok_count' => $counts['ok'],
                'feedback_count' => $counts['feedback'],
            ],
            'findings' => $findings,
        ];
    }

    /**
     * @return array{0: array{title_min: int, title_max: int, description_min: int, description_max: int, min_word_count: int}, 1: array<string, bool>}
     */
    private function loadConfiguration(Builder $builder): array
    {
        $seoConfig = (array) ($builder->getConfig()->get('doctor.seo') ?? []);

        $thresholds = [
            'title_min' => (int) ($seoConfig['title']['min'] ?? self::DEFAULT_CONFIG['title']['min']),
            'title_max' => (int) ($seoConfig['title']['max'] ?? self::DEFAULT_CONFIG['title']['max']),
            'description_min' => (int) ($seoConfig['description']['min'] ?? self::DEFAULT_CONFIG['description']['min']),
            'description_max' => (int) ($seoConfig['description']['max'] ?? self::DEFAULT_CONFIG['description']['max']),
            'min_word_count' => (int) ($seoConfig['content']['min_words'] ?? self::DEFAULT_CONFIG['content']['min_words']),
        ];

        $checks = array_merge(
            self::DEFAULT_CONFIG['checks'],
            (array) ($seoConfig['checks'] ?? [])
        );

        return [$thresholds, $checks];
    }

    /**
     * @param array{title_min: int, title_max: int, description_min: int, description_max: int, min_word_count: int} $thresholds
     * @param array<string, bool> $checks
     *
     * @return array<int, array{level: string, check: string, details: string}>
     */
    private function auditPage(Builder $builder, Page $page, array $thresholds, array $checks): array
    {
        $html = (string) $page->getRendered()['html']['output'];
        $xpath = $this->createXPath($html);
        if ($xpath === null) {
            return [[
                'level' => 'bad',
                'check' => 'Rendered HTML',
                'details' => 'The page could not be parsed as HTML.',
            ]];
        }

        $findings = [];

        if (($checks['title'] ?? false) === true) {
            $title = $this->getFirstNodeText($xpath, '//title');
            if ($title === '') {
                $findings[] = $this->createFinding('bad', 'Title tag', 'Missing <title> element.');
            } else {
                $titleLength = mb_strlen($title);
                if ($titleLength < $thresholds['title_min'] || $titleLength > $thresholds['title_max']) {
                    $findings[] = $this->createFinding(
                        'ok',
                        'Title length',
                        \sprintf('%d characters. Recommended: %d-%d.', $titleLength, $thresholds['title_min'], $thresholds['title_max'])
                    );
                }
            }
        }

        if (($checks['description'] ?? false) === true) {
            $description = $this->getFirstNodeText($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'description']/@content");
            if ($description === '') {
                $findings[] = $this->createFinding('bad', 'Meta description', 'Missing meta description.');
            } else {
                $descriptionLength = mb_strlen($description);
                if ($descriptionLength < $thresholds['description_min'] || $descriptionLength > $thresholds['description_max']) {
                    $findings[] = $this->createFinding(
                        'ok',
                        'Meta description length',
                        \sprintf('%d characters. Recommended: %d-%d.', $descriptionLength, $thresholds['description_min'], $thresholds['description_max'])
                    );
                }
            }
        }

        if (($checks['canonical'] ?? false) === true) {
            $canonical = $this->getFirstNodeText($xpath, "//link[contains(concat(' ', translate(normalize-space(@rel), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), ' '), ' canonical ')]/@href");
            if ($canonical === '') {
                $level = $builder->getConfig()->isEnabled('canonicalurl') ? 'bad' : 'feedback';
                $details = $builder->getConfig()->isEnabled('canonicalurl')
                    ? 'Missing canonical link while canonical URLs are enabled.'
                    : 'No canonical link found in the rendered page.';
                $findings[] = $this->createFinding($level, 'Canonical URL', $details);
            }
        }

        if (($checks['lang_attribute'] ?? false) === true) {
            $lang = $this->getFirstNodeText($xpath, '/html/@lang');
            if ($lang === '') {
                $findings[] = $this->createFinding('feedback', 'HTML lang attribute', 'Missing lang attribute on the <html> element.');
            }
        }

        if (($checks['h1'] ?? false) === true) {
            $h1Count = $this->countNodes($xpath, '//h1');
            if ($h1Count === 0) {
                $findings[] = $this->createFinding('bad', 'Heading structure', 'Missing <h1> element.');
            }
            if ($h1Count > 1) {
                $findings[] = $this->createFinding('ok', 'Heading structure', \sprintf('Found %d <h1> elements.', $h1Count));
            }
        }

        if (($checks['og_tags'] ?? false) === true) {
            if ($this->getFirstNodeText($xpath, "//meta[@property='og:title']/@content") === '') {
                $findings[] = $this->createFinding('feedback', 'Open Graph title', 'Missing og:title meta tag.');
            }
            if ($this->getFirstNodeText($xpath, "//meta[@property='og:description']/@content") === '') {
                $findings[] = $this->createFinding('feedback', 'Open Graph description', 'Missing og:description meta tag.');
            }
            if ($this->getFirstNodeText($xpath, "//meta[@property='og:image']/@content") === '') {
                $findings[] = $this->createFinding('feedback', 'Open Graph image', 'Missing og:image meta tag.');
            }
        }

        if (($checks['img_alt'] ?? false) === true) {
            $missingAltImages = $this->countNodes($xpath, "//img[not(@alt) or normalize-space(@alt) = '']");
            if ($missingAltImages > 0) {
                $findings[] = $this->createFinding('ok', 'Images alt text', \sprintf('%d image(s) are missing an alt attribute.', $missingAltImages));
            }
        }

        if (($checks['content_length'] ?? false) === true) {
            $wordCount = $this->countWords($this->getBodyText($xpath, $html));
            if ($wordCount < $thresholds['min_word_count']) {
                $findings[] = $this->createFinding('feedback', 'Content length', \sprintf('Estimated body length: %d words.', $wordCount));
            }
        }

        return $findings;
    }

    /**
     * @return array{level: string, check: string, details: string}
     */
    private function createFinding(string $level, string $check, string $details): array
    {
        return [
            'level' => $level,
            'check' => $check,
            'details' => $details,
        ];
    }

    private function getPageLabel(Builder $builder, Page $page): string
    {
        $path = (new PageRenderer($builder, $page))->getPath('html');
        if ($path === '') {
            return '/';
        }

        return '/' . ltrim($path, '/');
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

    private function getFirstNodeText(\DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        return $this->normalizeText((string) $nodes->item(0)?->textContent);
    }

    private function countNodes(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);
        if ($nodes === false) {
            return 0;
        }

        return $nodes->length;
    }

    private function getBodyText(\DOMXPath $xpath, string $html): string
    {
        $body = $this->getFirstNodeText($xpath, '//body');
        if ($body !== '') {
            return $body;
        }

        return $this->normalizeText((string) strip_tags($html));
    }

    private function countWords(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        preg_match_all("/[\\p{L}\\p{N}']+/u", $text, $matches);

        return \count($matches[0]);
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }
}
