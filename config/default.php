<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Website default configuration
return [
    'title'        => '',
    'baseline'     => '',
    'baseurl'      => 'http://localhost:8000/',
    'canonicalurl' => false,   // if true 'url()' function preprends URL wirh 'baseurl'
    'description'  => '',
    'taxonomies'   => [        // default taxonomies
        'tags'       => 'tag', // can be disabled with 'disabled' value
        'categories' => 'category',
    ],
    'pagination' => [
        'max'  => 5,      // number of pages on each paginated pages
        'path' => 'page', // path to paginated pages. ie: '/blog/page/2/'
    ],
    'date' => [
        'format'   => 'j F Y', // See https://php.net/manual/function.date.php
        'timezone' => 'Europe/Paris',
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
            'homepage'   => ['html', 'atom'],
            'section'    => ['html', 'atom'],
            'vocabulary' => ['html'],
            'term'       => ['html', 'atom'],
        ],
    ],
    'language'  => 'en',
    'languages' => [
        [
            'code'   => 'en',
            'name'   => 'English',
            'locale' => 'en_US',
        ],
    ],
    'defaultpages' => [
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
        ],
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
    ],
    // Markdown files
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
    // data files
    'data' => [
        'dir'  => 'data',
        'ext'  => ['yaml', 'yml', 'json', 'xml', 'csv'],
        'load' => true, // enables `site.data` collection
    ],
    // static files
    'static' => [
        'dir'     => 'static',
        'target'  => '',
        'exclude' => ['sass', 'scss', '*.scss', 'package*.json', 'node_modules'],
        'load'    => false, // enables `site.static` collection
    ],
    // templates
    'layouts' => [
        'dir'      => 'layouts',
        'internal' => [
            'dir' => 'resources/layouts',
        ],
    ],
    'themes' => [
        'dir' => 'themes',
    ],
    'assets' => [
        'compile' => [
            'enabled'   => true,
            'style'     => 'expanded', // 'expanded' or 'compressed',
            'import'    => ['sass', 'scss', 'node_modules'],
            'sourcemap' => true, // works in debug mode only
            //'variables' => ['var' => 'value']
        ],
        'minify' => [
            'enabled' => true,
        ],
        'fingerprint' => [
            'enabled' => true,
        ],
    ],
    'postprocess' => [
        'enabled' => false,
        'html'    => [
            'ext'     => ['html', 'htm'],
            'enabled' => true,
        ],
        'css' => [
            'ext'     => ['css'],
            'enabled' => true,
        ],
        'js' => [
            'ext'     => ['js'],
            'enabled' => true,
        ],
        'images' => [
            'ext'     => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg'],
            'enabled' => true,
        ],
    ],
    'cache' => [
        'dir'       => '.cache',
        'enabled'   => true,
        'templates' => [
            'dir'     => 'templates',
            'enabled' => true,
        ],
        'assets' => [
            'dir' => 'assets/remote',
        ],
    ],
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
];
