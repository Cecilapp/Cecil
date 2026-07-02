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

    /*public function createVideoIframe() {
        return [
            'extent' => $link['extent'],
            'element' => [
                'name' => 'div',
                'handler' => 'elements',
                'text' => [
                    $iframe['element'],
                ],

                'attributes' => [
                    'title' => $link['element']['attributes']['title'],
                    'style' => 'position:relative;padding-bottom:56.25%;height:0;overflow:hidden;',
                ],
            ],
        ];

        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;" title="TITLE">

        'name' => 'iframe',
        'text' => $link['element']['text'],
        'attributes' => [
            'src' => $url,
            'loading' => 'lazy',
            'width' => '640',
            'height' => '360',
            'title' => $link['element']['text'],
            'frameborder' => '0',
            'allow' => 'accelerometer;autoplay;encrypted-media;gyroscope;picture-in-picture;fullscreen;web-share;',
            'allowfullscreen' => '',
            'style' => 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;background-color:#d8d8d8;',
        ],

        <iframe
            width="640"
            height="360"
            title="TITLE"
            src="URL"
            frameborder="0"
            allow="accelerometer;autoplay;encrypted-media;gyroscope;picture-in-picture;fullscreen;web-share;"
            allowfullscreen
            style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;background-color:#d8d8d8;"
            >
            TEXT
        </iframe>

        <div>
    }*/
}
