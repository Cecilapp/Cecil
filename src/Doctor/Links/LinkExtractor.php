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

namespace Cecil\Doctor\Links;

/**
 * Extracts URLs and element IDs from HTML and CSS contents.
 */
class LinkExtractor
{
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

    /**
     * Extracts URLs from HTML attributes, inline styles and `<style>` elements.
     *
     * @return array<int, string>
     */
    public function extractHtmlLinks(string $html): array
    {
        $xpath = $this->createXPath($html);
        if ($xpath === null) {
            return [];
        }

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
     * Extracts URLs from `url()` and `@import` CSS statements.
     *
     * @return array<int, string>
     */
    public function extractCssLinks(string $css): array
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        preg_match_all('/url\(\s*([\'"]?)(.*?)\1\s*\)/i', $css, $urls);
        preg_match_all('/@import\s+([\'"])(.*?)\1/i', $css, $imports);

        return array_merge($urls[2], $imports[2]);
    }

    /**
     * Extracts element IDs (and anchor names) from HTML.
     *
     * @return array<string, true>
     */
    public function extractIds(string $html): array
    {
        $ids = [];
        foreach ($this->createXPath($html)?->query('//*[@id]/@id | //a[@name]/@name') ?: [] as $node) {
            if ($node instanceof \DOMNode) {
                $ids[$node->textContent] = true;
            }
        }

        return $ids;
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
