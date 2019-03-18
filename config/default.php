<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Default config
return [
    'site' => [
        'title'        => 'My Webiste',
        'baseline'     => 'An amazing static website!',
        'baseurl'      => 'http://localhost:8000/',
        'canonicalurl' => false,
        'description'  => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'taxonomies'   => [
            'tags'       => 'tag',
            'categories' => 'category',
        ],
        'pagination' => [
            'max'  => 5,
            'path' => 'page',
        ],
        'date' => [
            'format'   => 'j F Y',
            'timezone' => 'Europe/Paris',
        ],
        'virtualpages' => [
            'robots' => [
                'title'   => 'Robots.txt',
                'layout'  => 'robots',
                'output'  => 'txt',
            ],
            'sitemap' => [
                'title'      => 'XML sitemap',
                'layout'     => 'sitemap',
                'output'     => 'xml',
                'changefreq' => 'monthly',
                'priority'   => '0.5',
            ],
            '404' => [
                'title'   => 'Page not found',
                'layout'  => '404',
                'uglyurl' => true,
            ],
        ],
        'output' => [
            'dir'      => '_site',
            'formats'  => [
                // ie: blog/post-1/index.html
                'html' => [
                    'mediatype' => 'text/html',
                    'suffix'    => '/index',
                    'extension' => 'html',
                ],
                // ie: blog/rss.xml
                'rss' => [
                    'mediatype' => 'application/rss+xml',
                    'suffix'    => '/rss',
                    'extension' => 'xml',
                    'exclude'   => ['redirect', 'paginated'],
                ],
                // ie: blog/post-1.json
                'json' => [
                    'mediatype' => 'application/json',
                    'extension' => 'json',
                    'exclude'   => ['redirect'],
                ],
                // ie: blog/post-1.xml
                'xml' => [
                    'mediatype' => 'application/xml',
                    'extension' => 'xml',
                    'exclude'   => ['redirect'],
                ],
                // ie: robots.txt
                'txt' => [
                    'mediatype' => 'text/plain',
                    'extension' => 'txt',
                    'exclude'   => ['redirect'],
                ],
                // ie: blog/post-1/amp/index.html
                'amp' => [
                    'mediatype' => 'text/html',
                    'subpath'   => '/amp',
                    'suffix'    => '/index',
                    'extension' => 'html',
                ],
                // ie: sw.js
                'js' => [
                    'mediatype' => 'application/javascript',
                    'extension' => 'js',
                ],
                // ie: manifest.webmanifest
                'webmanifest' => [
                    'mediatype' => 'application/manifest+json',
                    'extension' => 'webmanifest',
                ],
            ],
            'pagetypeformats' => [
                'page'     => ['html', 'json'],
                'homepage' => ['html', 'rss', 'json'],
                'section'  => ['html', 'rss', 'json'],
                'taxonomy' => ['html', 'rss'],
                'terms'    => ['html'],
            ],
        ],
    ],
    'content' => [
        'dir' => 'content',
        'ext' => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'],
    ],
    'frontmatter' => [
        'format' => 'yaml',
    ],
    'body' => [
        'format' => 'md',
    ],
    'static' => [
        'dir' => 'static',
    ],
    'layouts' => [
        'dir'      => 'layouts',
        'internal' => [
            'dir' => 'res/layouts',
        ],
    ],
    'themes' => [
        'dir' => 'themes',
    ],
    'generators' => [
        //10 => 'Cecil\Generator\Section',
        //20 => 'Cecil\Generator\Taxonomy',
        30 => 'Cecil\Generator\Homepage',
        //40 => 'Cecil\Generator\Pagination',
        //50 => 'Cecil\Generator\Alias',
        //35 => 'Cecil\Generator\ExternalBody',
        //36 => 'Cecil\Generator\VirtualPages',
        //60 => 'Cecil\Generator\Redirect',
    ],
];
