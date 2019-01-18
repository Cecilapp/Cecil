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
        'paginate' => [
            'max'  => 5,
            'path' => 'page',
        ],
        'date' => [
            'format'   => 'j F Y',
            'timezone' => 'Europe/Paris',
        ],
        'fmpages' => [
            'robotstxt' => [
                'title'     => 'Robots.txt',
                'layout'    => 'robots.txt',
                'permalink' => 'robots.txt',
            ],
            'sitemap' => [
                'title'      => 'XML sitemap',
                'layout'     => 'sitemap.xml',
                'permalink'  => 'sitemap.xml',
                'changefreq' => 'monthly',
                'priority'   => '0.5',
            ],
            '404' => [
                'title'     => '404 page',
                'layout'    => '404.html',
                'permalink' => '404.html',
            ],
            'rss' => [
                'title'         => 'RSS file',
                'layout'        => 'rss.xml',
                'permalink'     => 'rss.xml',
                'targetsection' => 'blog',
            ],
        ],
        'output' => [
            'dir'      => '_site',
            'filename' => 'index.html',
            'formats' => [
                'html' => [
                    'mediatype' => 'text/html',
                    'filename'  => 'index.html',
                    'basename'  => 'index',
                    'extension' => 'html',
                ],
                'rss' => [
                    'mediatype' => 'application/rss+xml',
                    'filename'  => 'rss.xml',
                ],
                'json' => [
                    'mediatype' => 'application/json',
                    'filename'  => 'index.json',
                ],
            ],
            'bypagetype' => [
                'PAGE'     => ['html'],
                'HOMEPAGE' => ['html', 'rss'],
                'SECTION'  => ['html', 'rss'],
                'TAXONOMY' => ['html', 'rss'],
            ]
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
        10 => 'Cecil\Generator\Section',
        20 => 'Cecil\Generator\Taxonomy',
        30 => 'Cecil\Generator\Homepage',
        40 => 'Cecil\Generator\Pagination',
        50 => 'Cecil\Generator\Alias',
        35 => 'Cecil\Generator\ExternalBody',
        36 => 'Cecil\Generator\PagesFromConfig',
        60 => 'Cecil\Generator\Redirect',
    ],
];
