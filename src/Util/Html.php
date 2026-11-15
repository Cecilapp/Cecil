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

namespace Cecil\Util;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

/**
 * HTML utility class.
 *
 * This class provides utility methods for HTML manipulation.
 */
class Html
{
    /**
     * Extract Open Graph meta tags from HTML content.
     *
     * @param string $html The HTML content to parse
     *
     * @return array<string, string> An associative array of Open Graph meta tags
     */
    public static function getOpenGraphMetaTags(string $html): array
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $metaTags = $crawler->filterXPath('//meta[starts-with(@property, "og:")]');

        $ogTags = [];
        /** @var \DOMElement $metaTag */
        foreach ($metaTags as $metaTag) {
            $property = $metaTag->getAttribute('property');
            $content = $metaTag->getAttribute('content');
            $ogTags[$property] = $content;
        }

        return $ogTags;
    }

    /**
     * Extract Twitter meta tags from HTML content.
     *
     * @param string $html The HTML content to parse
     *
     * @return array<string, string> An associative array of Twitter meta tags
     */
    public static function getTwitterMetaTags(string $html): array
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $metaTags = $crawler->filterXPath('//meta[starts-with(@name, "twitter:")]');

        $twitterTags = [];
        /** @var \DOMElement $metaTag */
        foreach ($metaTags as $metaTag) {
            $name = $metaTag->getAttribute('name');
            $content = $metaTag->getAttribute('content');
            $twitterTags[$name] = $content;
        }

        return $twitterTags;
    }

    /**
     * Get the image URL from Open Graph or Twitter meta tags.
     *
     * @param string $html The HTML content to parse
     *
     * @return string|null The image URL if found, null otherwise
     */
    public static function getImageFromMetaTags(string $html): ?string
    {
        $ogTags = self::getOpenGraphMetaTags($html);
        if (isset($ogTags['og:image'])) {
            return $ogTags['og:image'];
        }

        $twitterTags = self::getTwitterMetaTags($html);
        if (isset($twitterTags['twitter:image'])) {
            return $twitterTags['twitter:image'];
        }

        return null;
    }

    /**
     * Get candidate image URLs from HTML content, ordered by priority.
     *
     * Fallbacks (in order):
     *   1. Open Graph (`og:image:secure_url`, `og:image`, `og:image:url`)
     *   2. Twitter (`twitter:image`, `twitter:image:src`)
     *   3. `<link rel="image_src">`
     *   4. Schema.org microdata (`itemprop="image"`)
     *   5. JSON-LD `image` property
     *   6. First `<img>` in `<article>`, `<main>` or `<body>`
     *   7. `<link rel="apple-touch-icon">`
     *   8. `<link rel="icon">`
     *
     * Relative URLs are resolved against `<base href>` or $baseUrl.
     *
     * @param string      $html    The HTML content to parse
     * @param string|null $baseUrl The URL of the HTML document
     *
     * @return string[] A list of unique absolute image URLs
     */
    public static function getImageCandidates(string $html, ?string $baseUrl = null): array
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $xpath = [
            '//meta[@property="og:image:secure_url"]/@content',
            '//meta[@property="og:image"]/@content',
            '//meta[@property="og:image:url"]/@content',
            '//meta[@name="twitter:image" or @property="twitter:image"]/@content',
            '//meta[@name="twitter:image:src" or @property="twitter:image:src"]/@content',
            '//link[@rel="image_src"]/@href',
            '//meta[@itemprop="image"]/@content',
            '//link[@itemprop="image"]/@href',
        ];

        $candidates = [];
        foreach ($xpath as $expression) {
            foreach ($crawler->filterXPath($expression) as $node) {
                $candidates[] = $node->nodeValue;
            }
        }
        // JSON-LD
        foreach ($crawler->filterXPath('//script[@type="application/ld+json"]') as $node) {
            $json = json_decode((string) $node->nodeValue, true);
            if (\is_array($json)) {
                array_push($candidates, ...self::getImagesFromJsonLd($json));
            }
        }
        // first image of the content
        foreach (['//article//img/@src', '//main//img/@src', '//body//img/@src'] as $expression) {
            $img = $crawler->filterXPath($expression);
            if ($img->count() > 0) {
                $candidates[] = $img->getNode(0)?->nodeValue;
                break;
            }
        }
        // icons
        foreach ($crawler->filterXPath('//link[contains(@rel, "apple-touch-icon")]/@href') as $node) {
            $candidates[] = $node->nodeValue;
        }
        foreach ($crawler->filterXPath('//link[@rel="icon" or @rel="shortcut icon"]/@href') as $node) {
            $candidates[] = $node->nodeValue;
        }

        // resolves relative URLs
        $base = $crawler->filterXPath('//base/@href');
        if ($base->count() > 0) {
            $baseUrl = UriResolver::resolve((string) $base->getNode(0)?->nodeValue, $baseUrl);
        }
        $urls = [];
        foreach ($candidates as $candidate) {
            $candidate = trim(html_entity_decode((string) $candidate));
            if ($candidate === '' || str_starts_with($candidate, 'data:')) {
                continue;
            }
            $urls[] = $baseUrl !== null ? UriResolver::resolve($candidate, $baseUrl) : $candidate;
        }

        return array_values(array_unique($urls));
    }

    /**
     * Makes root-relative URLs (e.g. `/about/`) of `href`, `src` and `srcset` attributes absolute.
     *
     * URLs are prefixed with the origin (scheme, host and port) of $baseUrl:
     * a root-relative URL already contains the path of the base URL, if any.
     * Absolute and protocol-relative URLs, relative paths, anchors, `mailto:`, `data:`, etc. are kept as is.
     *
     * @param string $html    The HTML content
     * @param string $baseUrl An absolute URL (e.g. `https://example.com/`)
     *
     * @return string The HTML content, unchanged if $baseUrl is not absolute
     */
    public static function absoluteUrls(string $html, string $baseUrl): string
    {
        $url = parse_url($baseUrl);
        if (empty($url['scheme']) || empty($url['host'])) {
            return $html;
        }
        $origin = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
        $absolute = fn (string $value): string => str_starts_with($value, '/') && !str_starts_with($value, '//') ? $origin . $value : $value;

        // HTML tags (escaped HTML, like in code blocks, is ignored)
        return (string) preg_replace_callback('/<[a-z][^\s\/>]*(?:"[^"]*"|\'[^\']*\'|[^\'">])*>/i', function (array $tag) use ($absolute): string {
            // attributes, with or without value
            return (string) preg_replace_callback('/(\s+)([^\s"\'>\/=]+)(?:(\s*=\s*)("[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?/', function (array $attribute) use ($absolute): string {
                $name = strtolower($attribute[2]);
                if (!isset($attribute[4]) || !\in_array($name, ['href', 'src', 'srcset'])) {
                    return $attribute[0];
                }
                $quote = \in_array($attribute[4][0], ['"', "'"]) ? $attribute[4][0] : '';
                $value = $quote ? substr($attribute[4], 1, -1) : $attribute[4];
                if ($name == 'srcset') {
                    // image candidates: a URL followed by an optional descriptor, separated by commas
                    $value = (string) preg_replace_callback('/(^|,)(\s*)([^\s,](?:\S*[^\s,])?)/', fn (array $candidate): string => $candidate[1] . $candidate[2] . $absolute($candidate[3]), $value);
                } else {
                    $value = $absolute($value);
                }

                return $attribute[1] . $attribute[2] . $attribute[3] . $quote . $value . $quote;
            }, $tag[0]);
        }, $html);
    }

    /**
     * Extracts image URLs from JSON-LD data.
     *
     * @param array<mixed> $data Decoded JSON-LD data (object or list of objects)
     *
     * @return string[]
     */
    private static function getImagesFromJsonLd(array $data): array
    {
        $images = [];
        if (isset($data['image'])) {
            $image = $data['image'];
            if (\is_string($image)) {
                $images[] = $image;
            } elseif (\is_array($image)) {
                foreach (array_is_list($image) ? $image : [$image] as $item) {
                    if (\is_string($item)) {
                        $images[] = $item;
                    } elseif (\is_array($item) && isset($item['url']) && \is_string($item['url'])) {
                        $images[] = $item['url'];
                    }
                }
            }
        }
        // graph or list of objects
        foreach (array_is_list($data) ? $data : ($data['@graph'] ?? []) as $item) {
            if (\is_array($item)) {
                array_push($images, ...self::getImagesFromJsonLd($item));
            }
        }

        return $images;
    }
}
