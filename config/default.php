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
    'title'        => 'My Website',
    'baseline'     => 'My amazing static website!',
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
    'language'  => 'en',
    'languages' => [
        [
            'code'   => 'en',
            'name'   => 'English',
            'locale' => 'en_US',
        ],
    ],
    'date'   => [
        'format'   => 'j F Y',
        'timezone' => 'Europe/Paris',
    ],
    'defaultpages' => [
        'robots' => [
            'path'      => 'robots',
            'title'     => 'Robots.txt',
            'layout'    => 'robots',
            'output'    => 'txt',
            'published' => true,
        ],
        'sitemap' => [
            'path'       => 'sitemap',
            'title'      => 'XML sitemap',
            'layout'     => 'sitemap',
            'output'     => 'xml',
            'changefreq' => 'monthly',
            'priority'   => '0.5',
            'published'  => true,
        ],
        '404' => [
            'path'      => '404',
            'title'     => 'Page not found',
            'layout'    => '404',
            'uglyurl'   => true,
            'published' => true,
        ],
    ],
    'output' => [
        'dir'      => '_site',
        'formats'  => [
            // ie: blog/post-1/index.html
            1000 => [
                'name'      => 'html',
                'mediatype' => 'text/html',
                'suffix'    => '/index',
                'extension' => 'html',
            ],
            // ie: blog/atom.xml
            1001 => [
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'suffix'    => '/atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // ie: blog/rss.xml
            1002 => [
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'suffix'    => '/rss',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // ie: blog/post-1.json
            1003 => [
                'name'      => 'json',
                'mediatype' => 'application/json',
                'extension' => 'json',
                'exclude'   => ['redirect'],
            ],
            // ie: blog/post-1.xml
            1004 => [
                'name'      => 'xml',
                'mediatype' => 'application/xml',
                'extension' => 'xml',
                'exclude'   => ['redirect'],
            ],
            // ie: robots.txt
            1005 => [
                'name'      => 'txt',
                'mediatype' => 'text/plain',
                'extension' => 'txt',
                'exclude'   => ['redirect'],
            ],
            // ie: blog/post-1/amp/index.html
            1006 => [
                'name'      => 'amp',
                'mediatype' => 'text/html',
                'subpath'   => '/amp',
                'suffix'    => '/index',
                'extension' => 'html',
            ],
            // ie: sw.js
            1007 => [
                'name'      => 'js',
                'mediatype' => 'application/javascript',
                'extension' => 'js',
            ],
            // ie: manifest.webmanifest
            1008 => [
                'name'      => 'webmanifest',
                'mediatype' => 'application/manifest+json',
                'extension' => 'webmanifest',
            ],
        ],
        'pagetypeformats' => [
            'page'       => ['html'],
            'homepage'   => ['html', 'atom', 'rss'],
            'section'    => ['html', 'atom', 'rss'],
            'vocabulary' => ['html'],
            'term'       => ['html', 'atom', 'rss'],
        ],
    ],
    'content' => [
        'dir' => 'content',
        'ext' => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'],
    ],
    'data' => [
        'dir' => 'data',
        'ext' => ['yaml', 'yml', 'json', 'xml', 'csv'],
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
    'optimize' => [
        'enabled' => false,
        'css'     => [
            'ext' => ['css'],
        ],
        'js' => [
            'ext' => ['js'],
        ],
        'html' => [
            'ext' => ['html', 'htm'],
        ],
        'images' => [
            'ext' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg'],
        ],
    ],
    'generators' => [
        10 => 'Cecil\Generator\Section',
        20 => 'Cecil\Generator\Taxonomy',
        30 => 'Cecil\Generator\Homepage',
        40 => 'Cecil\Generator\ExternalBody',
        50 => 'Cecil\Generator\DefaultPages',
        60 => 'Cecil\Generator\VirtualPages',
        70 => 'Cecil\Generator\Pagination',
        80 => 'Cecil\Generator\Alias',
        90 => 'Cecil\Generator\Redirect',
    ],
];
