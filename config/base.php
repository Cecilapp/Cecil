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

// Base configuration
return [
    'pages' => [
        'generators' => [
            10 => 'Cecil\Generator\DefaultPages',
            20 => 'Cecil\Generator\VirtualPages',
            30 => 'Cecil\Generator\ExternalBody',
            40 => 'Cecil\Generator\Section',
            50 => 'Cecil\Generator\Taxonomy',
            60 => 'Cecil\Generator\Homepage',
            70 => 'Cecil\Generator\Pagination',
            80 => 'Cecil\Generator\Alias',
            90 => 'Cecil\Generator\Redirect',
        ],
        'default' => [
            'index' => [
                'path'      => '',
                'title'     => 'Home',
                'published' => true,
            ],
            '404' => [
                'path'      => '404',
                'title'     => 'Page not found',
                'layout'    => '404',
                'uglyurl'   => true,
                'published' => true,
                'excluded'  => true,
            ],
            'robots' => [
                'path'         => 'robots',
                'title'        => 'Robots.txt',
                'layout'       => 'robots',
                'output'       => 'txt',
                'published'    => true,
                'excluded'     => true,
                'multilingual' => false,
            ],
            'sitemap' => [
                'path'         => 'sitemap',
                'title'        => 'XML sitemap',
                'layout'       => 'sitemap',
                'output'       => 'xml',
                'changefreq'   => 'monthly',
                'priority'     => '0.5',
                'published'    => true,
                'excluded'     => true,
                'multilingual' => false,
            ],
            'xsl/sitemap' => [
                'path'      => 'xsl/sitemap',
                'layout'    => 'sitemap',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => true,
                'excluded'  => true,
            ],
            'xsl/atom' => [
                'path'      => 'xsl/atom',
                'layout'    => 'feed',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => true,
                'excluded'  => true,
            ],
            'xsl/rss' => [
                'path'      => 'xsl/rss',
                'layout'    => 'feed',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => false,
                'excluded'  => true,
            ],
        ],
    ],
    'output' => [
        'formats'  => [
            [ // e.g.: blog/post-1/index.html
                'name'      => 'html',
                'mediatype' => 'text/html',
                'rel'       => 'canonical',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            [ // e.g.: blog/atom.xml
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'rel'       => 'alternate',
                'filename'  => 'atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: blog/rss.xml
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'rel'       => 'alternate',
                'filename'  => 'rss',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: blog.json
                'name'      => 'json',
                'mediatype' => 'application/json',
                'rel'       => 'alternate',
                'extension' => 'json',
                'exclude'   => ['redirect'],
            ],
            [ // e.g.: blog.xml
                'name'      => 'xml',
                'mediatype' => 'application/xml',
                'rel'       => 'alternate',
                'extension' => 'xml',
                'exclude'   => ['redirect'],
            ],
            [ // e.g.: robots.txt
                'name'      => 'txt',
                'mediatype' => 'text/plain',
                'rel'       => 'alternate',
                'extension' => 'txt',
                'exclude'   => ['redirect'],
            ],
            [ // e.g.: blog/post-1/amp/index.html
                'name'      => 'amp',
                'mediatype' => 'text/html',
                'rel'       => 'alternate',
                'subpath'   => 'amp',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            [ // e.g.: sw.js
                'name'      => 'js',
                'mediatype' => 'application/javascript',
                'rel'       => 'alternate',
                'extension' => 'js',
            ],
            [ // e.g.: manifest.webmanifest
                'name'      => 'webmanifest',
                'mediatype' => 'application/manifest+json',
                'rel'       => 'alternate',
                'extension' => 'webmanifest',
            ],
            [ // e.g.: atom.xsl
                'name'      => 'xsl',
                'mediatype' => 'application/xml',
                'rel'       => 'alternate',
                'extension' => 'xsl',
            ],
            [ // e.g.: blog/feed.json
                'name'      => 'jsonfeed',
                'mediatype' => 'application/json',
                'rel'       => 'alternate',
                'filename'  => 'feed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: video/oembed.json
                'name'      => 'oembed',
                'mediatype' => 'application/json+oembed',
                'rel'       => 'alternate',
                'filename'  => 'oembed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: video/embed.html
                'name'      => 'embed',
                'mediatype' => 'text/html',
                'rel'       => 'alternate',
                'filename'  => 'embed',
                'extension' => 'html',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: page.md
                'name'      => 'markdown',
                'mediatype' => 'text/markdown',
                'rel'       => 'alternate',
                'extension' => 'md',
            ],
            [ // e.g.: llms.txt
                'name'      => 'llms',
                'mediatype' => 'text/markdown',
                'rel'       => 'describedby',
                'filename'  => 'llms',
                'extension' => 'txt',
            ]
        ],
        'postprocessors' => [
            'GeneratorMetaTag' => 'Cecil\Renderer\PostProcessor\GeneratorMetaTag',
            'HtmlExcerpt'      => 'Cecil\Renderer\PostProcessor\HtmlExcerpt',
            'MarkdownLink'     => 'Cecil\Renderer\PostProcessor\MarkdownLink',
        ],
    ],
];
