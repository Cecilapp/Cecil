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
    'title'        => 'Site title',
    'baseline'     => 'Site baseline',
    'baseurl'      => 'http://localhost:8000/',
    'canonicalurl' => false,   // if true 'url()' function preprends URL wirh 'baseurl'
    'description'  => 'Site description',
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
            -1 => [
                'name'      => 'html',
                'mediatype' => 'text/html',
                'suffix'    => 'index',
                'extension' => 'html',
            ],
            // ie: blog/atom.xml
            -2 => [
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'suffix'    => 'atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // ie: blog/rss.xml
            -3 => [
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'suffix'    => 'rss',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // ie: blog/post-1.json
            -4 => [
                'name'      => 'json',
                'mediatype' => 'application/json',
                'extension' => 'json',
                'exclude'   => ['redirect'],
            ],
            // ie: blog/post-1.xml
            -5 => [
                'name'      => 'xml',
                'mediatype' => 'application/xml',
                'extension' => 'xml',
                'exclude'   => ['redirect'],
            ],
            // ie: robots.txt
            -6 => [
                'name'      => 'txt',
                'mediatype' => 'text/plain',
                'extension' => 'txt',
                'exclude'   => ['redirect'],
            ],
            // ie: blog/post-1/amp/index.html
            -7 => [
                'name'      => 'amp',
                'mediatype' => 'text/html',
                'subpath'   => 'amp',
                'suffix'    => 'index',
                'extension' => 'html',
            ],
            // ie: sw.js
            -8 => [
                'name'      => 'js',
                'mediatype' => 'application/javascript',
                'extension' => 'js',
            ],
            // ie: manifest.webmanifest
            -9 => [
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
            'exclude'   => true,
        ],
        'robots' => [
            'path'         => 'robots',
            'title'        => 'Robots.txt',
            'layout'       => 'robots',
            'output'       => 'txt',
            'published'    => true,
            'exclude'      => true,
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
            'exclude'      => true,
            'multilingual' => false,
        ],
    ],
    // Markdown files
    'content' => [
        'dir'    => 'content',
        'ext'    => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'],
    ],
    'frontmatter' => [
        'format' => 'yaml',
    ],
    'body' => [
        'format' => 'md',         // page body format (only Markdown is supported)
        'toc'    => ['h2', 'h3'], // headers used to build the table of contents
        'images' => [
            'lazy' => [
                'enabled' => true,  // enables lazy loading (`true` by default)
            ],
            'caption' => [
                'enabled' => false, // adds <figcaption> to images with a title (`false` by default)
            ],
            'remote' => [
                'enabled' => true,  // enables remote image handling (`true` by default)
            ],
            'resize' => [
                'enabled' => false, // enables image resizing by using the `width` extra attribute (`false` by default)
            ],
            'responsive' => [
                'enabled' => false, // creates responsive images (`false` by default)
            ],
            'webp' => [
                'enabled' => false, // creates WebP images (`false` by default)
            ],
        ],
        'notes' => [
            'enabled' => false,  // enables Notes blocks (`false` by default)
        ],
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
        'dir'     => 'assets',
        'compile' => [     // Compile Saas
            'enabled'   => true,
            'style'     => 'expanded', // 'expanded' or 'compressed',
            'import'    => ['sass', 'scss', 'node_modules'],
            'sourcemap' => false, // works in debug mode only
            //'variables' => ['var' => 'value']
        ],
        'minify' => [      // Minify CSS and JS
            'enabled' => true,
        ],
        'fingerprint' => [ // Add fingerprint
            'enabled' => true,
        ],
        'target' => 'assets', // target directory of remote and resized assets
        'images' => [
            'optimize' => [
                'enabled' => false, // enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp (`false` by default)
            ],
            'quality'    => 75,     // Image quality after optimization or resize (`75` by default)
            'responsive' => [
                'enabled' => false, // creates responsive images with `html` filter (`false` by default)
                'width'   => [
                    'steps' => 5,     // number of steps from `min` to `max` (`5` by default)
                    'min'   => 320,   // minimum width (`320` by default)
                    'max'   => 1280,  // maximum width (`1280` by default)
                ],
                'sizes' => [
                    'default' => '100vw', // `sizes` attribute (`100vw` by default)
                ],
            ],
            'webp' => [
                'enabled' => false, // creates a WebP version of images with `html` filter (`false` by default)
            ],
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
