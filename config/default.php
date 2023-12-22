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
    'baseline'     => '',
    'baseurl'      => 'http://localhost:8000/',
    'canonicalurl' => false, // if true then `url()` function prepends URL with `baseurl`
    'description'  => 'Site description',
    'author'       => [
        //'name'   => '',
        //'url'   => '',
        //'email' => '',
    ],
    //'image'        => '', // OG image
    'social'       => [
        //'social_network' => [
        //    'username' => '',
        //    'url'      => '',
        //]
    ],
    'date' => [
        'format'   => 'F j, Y', // @see https://www.php.net/manual/fr/datetime.format.php#refsect1-datetime.format-parameters
        //'timezone' => 'Europe/Paris',
    ],
    'language'  => 'en', // main language code (`en` by default)
    'languages' => [
        [
            'code'   => 'en',
            'name'   => 'English',
            'locale' => 'en_US',
        ],
    ],
    'theme' => [],
    'taxonomies'   => [ // default taxonomies
        'tags'       => 'tag',
        'categories' => 'category', // can be disabled with the special "disabled" value
    ],
    'pagination' => [
        'max'  => 5, // number of pages by each paginated pages
        'path' => 'page', // path to paginated pages (e.g.: `/blog/page/2/`)
    ],
    'pages' => [
        'dir'     => 'pages', // pages files directory (`pages` by default, previously `content`)
        'ext'     => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'], // supported files formats, by extension
        'exclude' => ['vendor', 'node_modules'], // directories, paths and files name to exclude (accepts globs, strings and regexes)
        'sortby'  => 'date', // default collections sort method
        //'sortby'  => [
        //    'variable'   => 'date', // date|updated|title|weight
        //    'desc_title' => false,  // false|true
        //    'reverse'    => false,  // false|true
        //],
        'frontmatter' => [
            'format' => 'yaml', // front matter format: `yaml`, `ini`, `toml` or `json` (`yaml` by default)
        ],
        'body' => [
            'format'    => 'markdown', // page body format (only Markdown is supported)
            'toc'       => ['h2', 'h3'], // headers used to build the table of contents
            'highlight' => [
                'enabled' => false, // enables code syntax highlighting (`false` by default)
            ],
            'images' => [
                'lazy' => [
                    'enabled' => true, // adds `loading="lazy"` attribute (`true` by default)
                ],
                'decoding' => [
                    'enabled' => true, // adds `decoding="async"` attribute (`true` by default)
                ],
                'resize' => [
                    'enabled' => false, // enables image resizing by using the `width` extra attribute (`false` by default)
                ],
                'webp' => [
                    'enabled' => false, // creates and adds a WebP image as a `source` (`false` by default)
                ],
                'responsive' => [
                    'enabled' => false, // creates responsive images and adds them to the `srcset` attribute (`false` by default)
                ],
                'class' => '', // puts default CSS class to each image (empty by default)
                'caption' => [
                    'enabled' => false, // puts the image in a <figure> element and adds a <figcaption> containing the title (`false` by default)
                ],
                'remote' => [
                    'enabled'  => true, // turns remote images to Asset to handling them (`true` by default)
                    'fallback' => [
                        'enabled' => false, // enables a fallback if image is not found (`false` by default)
                        'path'    => '', // path to the fallback image, stored in assets dir (empty by default)
                    ],
                ],
            ],
            'links' => [
                'embed' => [
                    'enabled' => false, // turns links in embedded content if possible (`false` by default)
                    'video'   => [
                        'ext' => ['mp4', 'webm'], // supported video file types, extensions
                    ],
                    'audio' => [
                        'ext' => ['mp3'], // supported audio file types, extensions
                    ],
                ],
            ],
            'excerpt' => [
                'separator' => 'excerpt|break', // string to use as separator (`excerpt|break` by default)
                'capture'   => 'before', // part to capture, `before` or `after` the separator (`before` by default)
            ],
        ],
        'generators' => [ // list of pages generators, ordered by weight
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
        'default' => [ // default generated pages
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
                'published' => false,
                'exclude'   => true,
            ],
        ],
    ],
    // data files
    'data' => [
        'dir'  => 'data', // data files directory (`data` by default)
        'ext'  => ['yaml', 'yml', 'json', 'xml', 'csv'], // loaded files by extension
        'load' => true, // enables `site.data` collection
    ],
    // static files
    'static' => [
        'dir'     => 'static', // static files directory (`static` by default)
        'target'  => '', // subdirectory where files are copied
        'exclude' => ['sass', 'scss', '*.scss', 'package*.json', 'node_modules'], // excluded files by extension or pattern
        'load'    => false, // enables `site.static` collection
    ],
    // assets: CSS, JS, images...
    'assets' => [
        'dir'    => 'assets', // assets files directory (`assets` by default)
        'target' => 'assets', // where remote and resized assets are saved
        'fingerprint' => [
            'enabled' => true, // enables fingerprinting (`true` by default)
        ],
        'compile' => [
            'enabled'   => true, // enables Sass files compilation (`true` by default)
            'style'     => 'expanded', // compilation style (`expanded` or `compressed`. `expanded`
            'import'    => ['sass', 'scss', 'node_modules'], // list of imported paths (`[sass, scss, node_modules]` by default)
            'sourcemap' => false, // enables sourcemap in debug mode (`false` by default)
            //'variables' => ['var' => 'value'], // list of preset variables (empty by default)
        ],
        'minify' => [
            'enabled' => true, // enables CSS et JS minification (`true` by default)
        ],
        'images' => [
            'resize' => [
                'dir' => 'thumbnails', // where resized images are stored (`thumbnails` by default)
            ],
            'optimize' => [
                'enabled' => false, // enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp (`false` by default)
            ],
            'quality'    => 75, // image quality after optimization or resize (`75` by default)
            'responsive' => [
                'widths' => [], // `srcset` widths (`[480, 640, 768, 1024, 1366, 1600, 1920]`
                'sizes'  => [
                    'default' => '100vw', // default `sizes` attribute (`100vw` by default)
                ],
                'enabled' => false, // `html` filter: creates responsive images (`false` by default)
            ],
            'webp' => [
                'enabled' => false, // `html` filter: creates and adds a WebP image as a `source` (`false` by default)
            ],
            'cdn' => [
                'enabled'   => false, // enables Image CDN (`false` by default)
                'canonical' => true, // is `image_url` must be canonical or not (`true` by default)
                'remote'    => true, // includes remote images (`true` by default)
                //'account'   => 'xxxx', // provider account
                // Cloudinary
                //'url'       => 'https://res.cloudinary.com/%account%/image/fetch/c_limit,w_%width%,q_%quality%,f_%format%,d_default/%image_url%',
                // Cloudimage
                //'url'       => 'https://%account%.cloudimg.io/%image_url%?w=%width%&q=%quality%&force_format=%format%',
                // TwicPics
                //'url'       => 'https://%account%.twic.pics/%image_url%?twic=v1/resize=%width%/quality=%quality%/output=%format%',
                // imgix
                //'url'       => 'https://%account%.imgix.net/%image_url%?w=%width%&q=%quality%&fm=%format%'
            ]
        ],
    ],
    // templates
    'layouts' => [
        'dir'      => 'layouts', // Twig templates directory (`layouts` by default)
        'internal' => [
            'dir' => 'resources/layouts', // internal templates directory
        ],
        'translations' => [ // i18n
            'dir'      => 'translations', // translations files directory (`translations` by default)
            'formats'  => ['yaml', 'mo'], // translations supported formats (`yaml` and `mo`)
            'internal' => [
                'dir' => 'resources/translations', // internal translations directory
            ],
        ],
        'extensions' => [], // Twig extensions
    ],
    // themes
    'themes' => [
        'dir' => 'themes', // where themes are stored (`themes` by default)
    ],
    'output' => [
        'dir'      => '_site', // output directory (`_site` by default)
        'formats'  => [ // https://cecil.app/documentation/configuration/#formats
            // e.g.: blog/post-1/index.html
            -1 => [
                'name'      => 'html',
                'mediatype' => 'text/html',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            // e.g.: blog/atom.xml
            -2 => [
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'filename'  => 'atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: blog/rss.xml
            -3 => [
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'filename'  => 'rss',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: blog.json
            -4 => [
                'name'      => 'json',
                'mediatype' => 'application/json',
                'extension' => 'json',
                'exclude'   => ['redirect'],
            ],
            // e.g.: blog.xml
            -5 => [
                'name'      => 'xml',
                'mediatype' => 'application/xml',
                'extension' => 'xml',
                'exclude'   => ['redirect'],
            ],
            // e.g.: robots.txt
            -6 => [
                'name'      => 'txt',
                'mediatype' => 'text/plain',
                'extension' => 'txt',
                'exclude'   => ['redirect'],
            ],
            // e.g.: blog/post-1/amp/index.html
            -7 => [
                'name'      => 'amp',
                'mediatype' => 'text/html',
                'subpath'   => 'amp',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            // e.g.: sw.js
            -8 => [
                'name'      => 'js',
                'mediatype' => 'application/javascript',
                'extension' => 'js',
            ],
            // e.g.: manifest.webmanifest
            -9 => [
                'name'      => 'webmanifest',
                'mediatype' => 'application/manifest+json',
                'extension' => 'webmanifest',
            ],
            // e.g.: atom.xsl
            -10 => [
                'name'      => 'xsl',
                'mediatype' => 'application/xml',
                'extension' => 'xsl',
            ],
            // e.g.: blog/feed.json
            -11 => [
                'name'      => 'jsonfeed',
                'mediatype' => 'application/json',
                'filename'  => 'feed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: video/embed.html
            -12 => [
                'name'      => 'iframe',
                'mediatype' => 'text/html',
                'filename'  => 'embed',
                'extension' => 'html',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: video/embed.json
            -13 => [
                'name'      => 'oembed',
                'mediatype' => 'application/json+oembed',
                'filename'  => 'embed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
        ],
        'pagetypeformats' => [ // formats applied by page type
            'page'       => ['html'],
            'homepage'   => ['html', 'atom'],
            'section'    => ['html', 'atom'],
            'vocabulary' => ['html'],
            'term'       => ['html', 'atom'],
        ],
        'postprocessors' => [ // list of output post processors
            -1 => 'Cecil\Renderer\PostProcessor\GeneratorMetaTag',
            -2 => 'Cecil\Renderer\PostProcessor\HtmlExcerpt',
            -3 => 'Cecil\Renderer\PostProcessor\MarkdownLink',
        ],
    ],
    'cache' => [
        'enabled'   => true, // enables cache support (`true` by default)
        'dir'       => '.cache', // cache files directory (`.cache` by default)
        'templates' => [
            'enabled' => true, // enables cache for Twig templates
            'dir'     => 'templates', // templates files cache directory (`templates` by default)
        ],
        'assets' => [
            'dir'    => 'assets', // assets files cache directory (`assets` by default)
            'remote' => [
                'dir' => 'remote', // remote files cache directory (`remote` by default)
            ],
        ],
        'translations' => [
            'enabled' => true, // enables cache for translations dictionary
            'dir'     => 'translations', // translations files cache directory (`assets` by default)
        ],
    ],
    'optimize' => [
        'enabled' => false, // enables files optimization (`false` by default)
        'html'    => [
            'enabled' => true, // enables HTML files optimization
            'ext'     => ['html', 'htm'], // supported files extensions
        ],
        'css' => [
            'enabled' => true, // enables CSS files optimization
            'ext'     => ['css'], // supported files extensions
        ],
        'js' => [
            'enabled' => true, // enables JavaScript files optimization
            'ext'     => ['js'], // supported files extensions
        ],
        'images' => [
            'enabled' => true, // enables images files optimization
            'ext'     => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg'], // supported files extensions
        ],
    ],
];
