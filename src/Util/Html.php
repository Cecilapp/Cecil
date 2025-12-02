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
     * @return array An associative array of Open Graph meta tags
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
     * @return array An associative array of Twitter meta tags
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
}
