<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Website default configuration
return [
    'title'        => 'Site title',
    //'baseline'     => 'Site baseline',
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
        'format'   => 'j F Y', // @see https://www.php.net/manual/fr/datetime.format.php#refsect1-datetime.format-parameters
        //'timezone' => 'Europe/Paris',
    ],
    'output' => [
        'dir'      => '_site',
        'formats'  => [
            // ie: blog/post-1/index.html
            -1 => [
                'name'      => 'html',
                'mediatype' => 'text/html',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            // ie: blog/atom.xml
            -2 => [
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'filename'  => 'atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // ie: blog/rss.xml
            -3 => [
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'filename'  => 'rss',
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
                'filename'  => 'index',
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
            // ie: atom.xsl
            -10 => [
                'name'      => 'xsl',
                'mediatype' => 'application/xml',
                'extension' => 'xsl',
            ],
            // ie: blog/feed.json
            -11 => [
                'name'      => 'jsonfeed',
                'mediatype' => 'application/json',
                'filename'  => 'feed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
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
        'xsl/atom' => [
            'path'      => 'xsl/atom',
            'layout'    => 'feed',
            'output'    => 'xsl',
            'uglyurl'   => true,
            'published' => true,
            'exclude'   => true,
        ],
        'xsl/rss' => [
            'path'      => 'xsl/rss',
            'layout'    => 'feed',
            'output'    => 'xsl',
            'uglyurl'   => true,
            'published' => true,
            'exclude'   => true,
        ],
    ],
    // Markdown files
    'pages' => [
        'dir'     => 'pages', // pages directory (previously "content")
        'ext'     => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'], // array of files extensions
        'exclude' => ['vendor', 'node_modules'], // array of directories, paths and files name to exclude
    ],
    'frontmatter' => [
        'format' => 'yaml', // front matter format `yaml`, `ini`, `toml` or `json` (`yaml` by default)
    ],
    'body' => [
        'format'    => 'md',         // page body format (only Markdown is supported)
        'toc'       => ['h2', 'h3'], // headers used to build the table of contents
        'highlight' => [
            'enabled' => false,      // enables code syntax highlighting (`false` by default)
        ],
        'images' => [
            'lazy' => [
                'enabled' => true,  // adds `loading="lazy"` attribute (`true` by default)
            ],
            'decoding' => 'async',  // adds `decoding="<value>"` attribute (`async` by default. 'sync', 'async' or 'auto')
            'class' => '',          // put default class to each image (empty by default)
            'resize' => [
                'enabled' => false, // enables image resizing by using the `width` extra attribute (`false` by default)
            ],
            'responsive' => [
                'enabled' => false, // creates responsive images and add them to the `srcset` attribute (`false` by default)
            ],
            'webp' => [
                'enabled' => false, // adds a WebP image as a `source` (`false` by default)
            ],
            'caption' => [
                'enabled' => false, // puts the image in a <figure> element and adds a <figcaption> containing the title (`false` by default)
            ],
            'remote' => [
                'enabled'  => true, // turns remote images to Asset to handling them (`true` by default)
                'fallback' => [
                    'enabled' => false, // enables a fallback if image is not found (`false` by default)
                    'path'    => '',    // path to the fallback image, stored in assets dir (empty by default)
                ],
            ],
        ],
        'links' => [
            'embed' => [
                'enabled' => false, // turns links in embedded content if possible (`false` by default)
                'video'   => [
                    'ext' => ['mp4', 'webm'], // video files extensions
                ],
                'audio' => [
                    'ext' => ['mp3'], // audio files extensions
                ],
            ],
        ],
        'excerpt' => [
            'separator' => 'excerpt|break', // string to use as separator (`excerpt|break` by default)
            'capture'   => 'before',        // part to capture, `before` or `after` the separator (`before` by default)
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
    // i18n
    'translations' => [
        'dir'      => 'translations',
        'formats'  => ['yaml', 'mo'],
        'internal' => [
            'dir' => 'resources/translations',
        ],
    ],
    'assets' => [
        'dir'     => 'assets',
        'compile' => [ // Compile Saas
            'enabled'   => true,
            'style'     => 'expanded', // 'expanded' or 'compressed',
            'import'    => ['sass', 'scss', 'node_modules'],
            'sourcemap' => false, // works in debug mode only
            //'variables' => ['var' => 'value']
        ],
        'minify' => [ // Minify CSS and JS
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
            'quality'    => 75, // image quality after optimization or resize (`75` by default)
            'responsive' => [
                'enabled' => false, // creates responsive images with `html` filter (`false` by default)
                'widths'  => [480, 640, 768, 1024, 1366, 1600, 1920],
                'sizes'   => [
                    'default' => '100vw', // `sizes` attribute (`100vw` by default)
                ],
            ],
            'webp' => [
                'enabled' => false, // creates a WebP version of images with `html` filter (`false` by default)
            ],
            'resize' => [
                'dir' => 'thumbnails', // directory to store resized assets (`thumbnails` by default)
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
            'dir'    => 'assets',
            'remote' => [
                'dir' => 'remote',
            ],
        ],
        'translations' => [
            'dir'     => 'translations',
            'enabled' => true,
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
